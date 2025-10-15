<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ServiceRecord;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
use App\Models\ActivityLog;

class UserController extends Controller
{
    /**
     * Definiert die Hierarchie der Ränge als private Eigenschaft.
     * @var array
     */
    private $rankHierarchy = [
        'ems-director'             => 8,
        'assistant-ems-director'   => 7,
        'instructor'               => 6,
        'emergency-doctor'         => 5,
        'paramedic'                => 4,
        'emt'                      => 3,
        'emt-trainee'              => 2,
        'praktikant'               => 1,
    ];

    /**
     * Definiert Abteilungen und deren Rollen-Hierarchie.
     * @var array
     */
    private $departmentalRoles = [
        'Rechtsabteilung' => [
            'leitung_role' => 'rechtsabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Rechtsabteilung - leitung',
                'Rechtsabteilung - mitglied',
            ],
        ],
        'Ausbildungsabteilung' => [
            'leitung_role' => 'ausbildungsabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Ausbildungsabteilung - leitung',
                'Ausbildungsabteilung - ausbilder',
                'Ausbildungsabteilung - ausbilder auf probe',
            ],
        ],
        'Personalabteilung' => [
            'leitung_role' => 'personalabteilung - leitung',
            'min_rank_to_assign_leitung' => 7, // Assistant EMS Director
            'roles' => [
                'Personalabteilung - leitung',
                'Personalabteilung - mitglied',
            ],
        ],
    ];

    /**
     * Definiert die unsichtbare Super-Admin Rolle.
     * @var string
     */
    private $superAdminRole = 'Super-Admin';


    public function __construct()
    {
        $this->middleware('can:users.view')->only('index');
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.edit')->only(['edit', 'update']);
        $this->middleware('can:users.manage.record')->only('addRecord');
    }

    /**
     * Gibt eine gefilterte Liste der Rollen zurück, die der aktuelle Admin verwalten darf.
     */
    private function getManagableRoles()
    {
        $admin = Auth::user();

        // Ausnahme: Director und Super-Admin dürfen immer alle Rollen verwalten (außer Super-Admin selbst).
        if ($admin->hasAnyRole('ems-director', $this->superAdminRole)) {
            return Role::where('name', '!=', $this->superAdminRole)->get();
        }

        $adminRankLevel = $admin->getHighestRankLevel();
        $adminRoleNames = $admin->getRoleNames();

        $allRoles = Role::where('name', '!=', $this->superAdminRole)->get(); // Super-Admin ausschließen
        $managableRoles = collect();

        foreach ($allRoles as $role) {
            // 1. Rang-Rollen prüfen
            if (isset($this->rankHierarchy[$role->name])) {
                if ($this->rankHierarchy[$role->name] < $adminRankLevel) {
                    $managableRoles->push($role);
                }
                continue;
            }

            // 2. Abteilungs-Rollen prüfen
            foreach ($this->departmentalRoles as $department) {
                if (in_array($role->name, $department['roles'])) {
                    if ($role->name === $department['leitung_role']) {
                        if ($adminRankLevel >= $department['min_rank_to_assign_leitung']) {
                            $managableRoles->push($role);
                        }
                    } else {
                        if ($adminRoleNames->contains($department['leitung_role'])) {
                            $managableRoles->push($role);
                        }
                    }
                    break;
                }
            }
        }

        return $managableRoles->unique('id');
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->getManagableRoles();
        $statuses = [
            'Aktiv', 'Probezeit', 'Beobachtung', 'Beurlaubt', 'Krankgeschrieben',
            'Suspendiert', 'Ausgetreten', 'Bewerbungsphase',
        ];
        return view('admin.users.create', compact('roles', 'statuses'));
    }

    public function store(Request $request)
    {
        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'cfx_id' => 'required|string|unique:users,cfx_id',
            'status' => 'required|string',
            'roles' => 'sometimes|array',
            'roles.*' => [Rule::in($managableRoleNames)],
        ]);

        $selectedRoles = $request->roles ?? [];
        $highestRankName = 'praktikant';
        $highestLevel = 0;

        foreach ($selectedRoles as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
                $highestRankName = $roleName;
            }
        }
        $validatedData['rank'] = $highestRankName;
        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';
        
        do {
            $newEmployeeId = rand(10000, 99999);
        } while (User::where('employee_id', $newEmployeeId)->exists());
        $validatedData['employee_id'] = $newEmployeeId;

        $validatedData['hire_date'] = now();
        $validatedData['last_edited_by'] = Auth::user()->name;
        $validatedData['last_edited_at'] = now();
        
        $user = User::create($validatedData);
        $user->syncRoles($selectedRoles);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'log_type' => 'USER',
            'action' => 'CREATED',
            'target_id' => $user->id,
            'description' => "Neuer Mitarbeiter '{$user->name}' ({$user->id}) angelegt.",
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Mitarbeiter erfolgreich angelegt.');
    }
    
    public function show(User $user)
    {
        $user->load([
            'serviceRecords' => fn($q) => $q->with('author')->latest(), 
            'examinations', 
            'trainingModules', 
            'vacations',
            'receivedEvaluations' => fn($q) => $q->with('evaluator')->latest(),
        ]);
        $serviceRecords = $user->serviceRecords()->with('author')->latest()->get();       
        $evaluationCounts = $this->calculateEvaluationCounts($user);
        return view('profile.show', compact('user','serviceRecords','evaluationCounts'));
    }
    private function calculateEvaluationCounts(User $user): array
    {
        $currentUserId = $user->id;
        $evaluatorId = Auth::id();

        $counts = ['verfasst' => [], 'erhalten' => [], 'gesamt' => []];
        $typeLabels = ['azubi', 'praktikant', 'mitarbeiter', 'leitstelle'];
        
        foreach ($typeLabels as $type) {
            $counts['verfasst'][$type] = 0;
            $counts['erhalten'][$type] = 0;
        }
        
        $allEvaluations = Evaluation::where('user_id', $currentUserId)
                                      ->orWhere('evaluator_id', $evaluatorId)
                                      ->get();

        foreach ($allEvaluations as $evaluation) {
            $type = $evaluation->evaluation_type;

            if (!isset($counts['verfasst'][$type])) continue;

            if ($evaluation->user_id === $currentUserId) {
                $counts['erhalten'][$type]++;
            }

            if ($evaluation->evaluator_id === $evaluatorId) {
                $counts['verfasst'][$type]++;
            }
        }
        
        return $counts;
    }
    public function edit(User $user)
    {
        $statuses = [
            'Aktiv', 'Beurlaubt', 'Beobachtung', 'Krankgeschrieben',
            'Suspendiert', 'Ausgetreten', 'Bewerbungsphase', 'Probezeit',
        ];
        $roles = $this->getManagableRoles();
        $permissions = Permission::all();
        $allPossibleNumbers = range(1, 150);
        $takenNumbers = User::where('status', 'Aktiv')->where('id', '!=', $user->id)->pluck('personal_number')->toArray();
        $availablePersonalNumbers = array_diff($allPossibleNumbers, $takenNumbers);
        return view('admin.users.edit', compact('user', 'roles', 'permissions', 'availablePersonalNumbers', 'statuses'));
    }

    public function update(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'roles' => 'sometimes|array',
            'permissions' => 'sometimes|array',
            'status' => 'required|string',
            'personal_number' => ['required', 'integer', Rule::unique('users')->ignore($user->id)],
            'employee_id' => 'nullable|string', 'email' => 'nullable|email',
            'birthday' => 'nullable|date', 'discord_name' => 'nullable|string',
            'forum_name' => 'nullable|string', 'special_functions' => 'nullable|string',
            'hire_date' => 'nullable|date',
        ]);

        $managableRoleNames = $this->getManagableRoles()->pluck('name')->toArray();
        $originalRoleNames = $user->getRoleNames()->toArray();
        $submittedRoleNames = $request->input('roles', []);

        $newlyAddedRoles = array_diff($submittedRoleNames, $originalRoleNames);

        foreach ($newlyAddedRoles as $addedRole) {
            if (!in_array($addedRole, $managableRoleNames)) {
                return redirect()->back()
                    ->withErrors(['roles' => 'Sie haben nicht die Berechtigung, die Rolle "' . $addedRole . '" zuzuweisen.'])
                    ->withInput();
            }
        }
        
        $validatedData['second_faction'] = $request->has('second_faction') ? 'Ja' : 'Nein';
        $oldRank = $user->rank;
        $oldStatus = $user->status;

        $newRank = 'praktikant';
        $highestLevel = 0;
        foreach ($submittedRoleNames as $roleName) {
            if (isset($this->rankHierarchy[$roleName]) && $this->rankHierarchy[$roleName] > $highestLevel) {
                $highestLevel = $this->rankHierarchy[$roleName];
                $newRank = $roleName;
            }
        }
        $validatedData['rank'] = $newRank;

        $user->syncRoles($submittedRoleNames);
        $user->syncPermissions($request->permissions ?? []);
        
        $validatedData['last_edited_at'] = now();
        $validatedData['last_edited_by'] = Auth::user()->name;
        $user->update($validatedData);

        $description = "Benutzerprofil von '{$user->name}' ({$user->id}) aktualisiert.";
        if ($oldRank !== $newRank) {
            $description .= " Rang geändert: {$oldRank} -> {$newRank}.";
        }
        if ($oldStatus !== $validatedData['status']) {
            $description .= " Status geändert: {$oldStatus} -> {$validatedData['status']}.";
        }
        
        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'USER', 'action' => 'UPDATED',
            'target_id' => $user->id, 'description' => $description,
        ]);

        if ($oldRank !== $newRank) {
            $recordType = $highestLevel > ($this->rankHierarchy[$oldRank] ?? 0) ? 'Beförderung' : 'Degradierung';
            ServiceRecord::create(['user_id' => $user->id, 'author_id' => Auth::id(), 'type' => $recordType, 'content' => "Rang geändert von '{$oldRank}' zu '{$newRank}'."]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Mitarbeiter erfolgreich aktualisiert.');
    }
    
    public function addRecord(Request $request, User $user)
    {
        $request->validate(['type' => 'required|string', 'content' => 'required|string']);
        
        ServiceRecord::create([
            'user_id' => $user->id, 'author_id' => Auth::id(), 
            'type' => $request->type, 'content' => $request->content
        ]);
        
        $user->update(['last_edited_at' => now(), 'last_edited_by' => Auth::user()->name]);

        ActivityLog::create([
            'user_id' => Auth::id(), 'log_type' => 'USER_RECORD', 'action' => 'ADDED',
            'target_id' => $user->id,
            'description' => "Eintrag (Typ: {$request->type}) zur Personalakte von '{$user->name}' hinzugefügt.",
        ]);

        return redirect()->route('admin.users.show', $user)->with('success', 'Eintrag zur Personalakte hinzugefügt.');
    }
}

