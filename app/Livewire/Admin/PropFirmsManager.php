<?php

namespace App\Livewire\Admin;

use App\Models\PropFirm;
use App\Models\Program;
use App\Models\ProgramLevel;
use App\Models\ProgramObjective; // Necesario para lógica interna
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\AuthActions; // Tu trait

class PropFirmsManager extends Component
{
    use WithFileUploads, AuthActions;

    // Campos vinculados a formularios (Entangled)
    public $firmForm = ['id' => null, 'name' => '', 'website' => '', 'server' => '', 'logo' => null];
    public $programForm = ['id' => null, 'firm_id' => null, 'name' => '', 'step_count' => 1];
    public $levelForm = ['id' => null, 'program_id' => null, 'name' => '', 'size' => 10000, 'fee' => 0, 'currency' => 'USD'];

    // Objetivos dinámicos
    public $objectivesForm = [];

    public function mount()
    {
        if (!$this->isSuperAdmin(Auth::user())) {
            abort(403);
        }
    }

    /**
     * Devuelve el árbol completo de datos para Alpine.
     * Esto evita round-trips al navegar.
     */
    public function getTreeData()
    {
        return PropFirm::with([
            'programs' => function ($q) {
                $q->orderBy('name');
            },
            'programs.levels' => function ($q) {
                $q->orderBy('size');
            },
            'programs.levels.objectives' => function ($q) {
                $q->orderBy('phase_number');
            }
        ])->orderBy('name')->get()->toArray();
    }

    // --- CRUD EMPRESAS ---

    public function saveFirm()
    {
        $this->validate([
            'firmForm.name' => 'required',
            'firmForm.website' => 'required|url',
            'firmForm.server' => 'required',
        ]);

        $data = [
            'name' => $this->firmForm['name'],
            'slug' => Str::slug($this->firmForm['name']),
            'website' => $this->firmForm['website'],
            'server' => $this->firmForm['server'],
        ];

        if ($this->firmForm['logo']) {
            $data['logo_path'] = $this->firmForm['logo']->store('prop-logos', 'public');
        }

        PropFirm::updateOrCreate(['id' => $this->firmForm['id'] ?? null], $data);

        // 1. Refrescar datos en JS (silent update)
        $this->dispatch('refresh-tree', tree: $this->getTreeData());

        // 2. Lanzar alerta visual
        $this->dispatch('notify', message: 'Empresa guardada correctamente', type: 'success');
    }

    // --- CRUD PROGRAMAS ---

    public function saveProgram()
    {
        $this->validate([
            'programForm.firm_id' => 'required|exists:prop_firms,id',
            'programForm.name' => 'required',
            'programForm.step_count' => 'required|in:0,1,2,3',
        ]);

        $firm = PropFirm::find($this->programForm['firm_id']); // Obtener objeto para slug correcto

        Program::updateOrCreate(
            ['id' => $this->programForm['id'] ?? null],
            [
                'prop_firm_id' => $this->programForm['firm_id'],
                'name' => $this->programForm['name'],
                'slug' => Str::slug($firm->name . '-' . $this->programForm['name']),
                'step_count' => $this->programForm['step_count'],
            ]
        );

        $this->dispatch('refresh-tree', tree: $this->getTreeData());
        $this->dispatch('notify', message: 'Programa creado correctamente', type: 'success');
    }

    // --- CRUD NIVELES (Complejo) ---

    public function saveLevel()
    {
        $this->validate([
            'levelForm.program_id' => 'required|exists:programs,id',
            'levelForm.name' => 'required',
            'levelForm.size' => 'required|numeric',
        ]);

        DB::transaction(function () {
            // 1. Guardar Nivel
            $level = ProgramLevel::updateOrCreate(
                ['id' => $this->levelForm['id'] ?? null],
                [
                    'program_id' => $this->levelForm['program_id'],
                    'name' => $this->levelForm['name'],
                    'currency' => $this->levelForm['currency'],
                    'size' => $this->levelForm['size'],
                    'fee' => $this->levelForm['fee'],
                ]
            );

            // 2. Guardar Objetivos
            // Borramos los objetivos existentes para recrearlos limpiamente (más seguro ante cambios de estructura)
            // O usamos updateOrCreate. Dado que objectivesForm viene completo del front:
            foreach ($this->objectivesForm as $phaseNum => $objData) {
                $level->objectives()->updateOrCreate(
                    [
                        'phase_number' => $phaseNum, // Clave única compuesta lógica
                        'program_level_id' => $level->id
                    ],
                    [
                        'name' => $objData['name'],
                        'profit_target_percent' => $objData['profit_target_percent'] === '' ? null : $objData['profit_target_percent'],
                        'max_daily_loss_percent' => $objData['max_daily_loss_percent'],
                        'max_total_loss_percent' => $objData['max_total_loss_percent'],
                        'min_trading_days' => $objData['min_trading_days'],
                        'loss_type' => $objData['loss_type'],
                        'rules_metadata' => isset($objData['rules_metadata']) ? json_encode($objData['rules_metadata']) : null,
                    ]
                );
            }
        });

        $this->dispatch('refresh-tree', tree: $this->getTreeData());
        $this->dispatch('notify', message: 'Nivel configurado con éxito', type: 'success');
    }

    public function duplicateLevel($levelId)
    {
        DB::transaction(function () use ($levelId) {
            $original = ProgramLevel::with('objectives')->findOrFail($levelId);

            // 1. Clonar Nivel
            $newLevel = $original->replicate();
            $newLevel->name = $original->name . ' (Copia)';
            $newLevel->save();

            // 2. Clonar Objetivos
            foreach ($original->objectives as $objective) {
                $newObjective = $objective->replicate();
                $newObjective->program_level_id = $newLevel->id;
                $newObjective->save();
            }
        });

        $this->dispatch('refresh-tree', tree: $this->getTreeData());
        $this->dispatch('notify', 'Nivel duplicado con éxito');
    }

    public function deleteLevel($id)
    {
        // El delete cascade de la BD borrará los objetivos
        ProgramLevel::destroy($id);
        $this->dispatch('refresh-tree', tree: $this->getTreeData());
        $this->dispatch('notify', 'Nivel eliminado');
    }

    public function render()
    {
        return view('livewire.admin.prop-firms-manager');
    }
}
