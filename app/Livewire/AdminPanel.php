<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Admin\GetMt5MonitorStats;
use App\Actions\Admin\GetOverviewKpis;
use App\Actions\Admin\GetQueueStats;
use App\Actions\Admin\GetStorageStats;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminPanel extends Component
{
    use WithPagination;

    // ─── Tab activo (sincronizado con URL ?tab=overview) ──────────────────
    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    // ─── Filtros de la sección Usuarios ───────────────────────────────────
    public string $search = '';
    public string $filterStatus = 'all'; // all | active | banned

    // ─────────────────────────────────────────────────────────────────────
    // COMPUTED: Usuarios
    // Se recalcula automáticamente cuando $search o $filterStatus cambian.
    // Usamos #[Computed] para que Livewire lo cachee dentro del mismo request.
    // ─────────────────────────────────────────────────────────────────────

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return \App\Models\User::query()
            ->withCount(['trades', 'accounts'])
            ->when(
                filled($this->search),
                fn(Builder $q) => $q->where(function (Builder $inner): void {
                    $inner->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                })
            )
            ->when(
                $this->filterStatus === 'active',
                fn(Builder $q) => $q->where('is_banned', false)
            )
            ->when(
                $this->filterStatus === 'banned',
                fn(Builder $q) => $q->where('is_banned', true)
            )
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    // ─────────────────────────────────────────────────────────────────────
    // OVERVIEW — delega en Action class (lógica > 10 líneas fuera del componente)
    // ─────────────────────────────────────────────────────────────────────

    /** @return array<string, int> */
    public function getOverviewKpis(): array
    {
        return app(GetOverviewKpis::class)->execute();
    }

    // ─────────────────────────────────────────────────────────────────────
    // ALMACENAMIENTO
    // ─────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function getStorageStats(): array
    {
        return app(GetStorageStats::class)->execute();
    }

    // ─────────────────────────────────────────────────────────────────────
    // COLAS / JOBS
    // ─────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function getQueueStats(): array
    {
        return app(GetQueueStats::class)->execute();
    }

    /** @return array<string, mixed> */
    public function getMt5Stats(): array
    {
        return app(GetMt5MonitorStats::class)->execute();
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACCIONES — BAN / UNBAN
    // ─────────────────────────────────────────────────────────────────────

    public function banUser(int $id): void
    {
        $user = \App\Models\User::findOrFail($id);

        // Nunca permitir que el admin se banee a sí mismo
        abort_if($user->id === Auth::id(), 403);

        $user->update(['is_banned' => true]);

        $this->dispatch('notify', type: 'warning', message: "Usuario {$user->name} baneado.");
    }

    public function unbanUser(int $id): void
    {
        $user = \App\Models\User::findOrFail($id);
        $user->update(['is_banned' => false]);

        $this->dispatch('notify', type: 'success', message: "Usuario {$user->name} desbaneado.");
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACCIONES — COLAS
    // ─────────────────────────────────────────────────────────────────────

    public function clearFailedJobs(): void
    {
        DB::table('failed_jobs')->truncate();
        $this->dispatch('notify', type: 'success', message: 'Jobs fallidos eliminados.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // LIFECYCLE — reset paginación al filtrar
    // ─────────────────────────────────────────────────────────────────────

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = 'all';
        $this->resetPage();
    }

    // Añade el método vacío
    /** @return array<string, mixed> */
    private function emptyMt5Stats(): array
    {
        return [
            'totals'         => ['total' => 0, 'sync_enabled' => 0, 'with_errors' => 0, 'stale' => 0, 'healthy' => 0],
            'error_accounts' => [],
            'stale_accounts' => [],
            'recent_syncs'   => [],
            'stale_hours'    => 2,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyStorageStats(): array
    {
        return [
            'total_bytes'     => 0,
            'total_files'     => 0,
            'formatted_total' => '0 B',
            'limit_bytes'     => (int) config('tradeforge.storage_limit_bytes', 10 * 1024 ** 3),
            'formatted_limit' => '10 GB',
            'used_percent'    => 0.0,
            'top_users'       => [],
            'source'          => 'cloudflare',
            'error'           => null,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyQueueStats(): array
    {
        return [
            'failed_count'  => 0,
            'pending_count' => 0,
            'last_job'      => null,
            'horizon_stats' => null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin-panel', [
            'kpis'         => $this->getOverviewKpis(),
            'storageStats' => $this->activeTab === 'storage' ? $this->getStorageStats() : $this->emptyStorageStats(),
            'queueStats'   => $this->activeTab === 'queues'  ? $this->getQueueStats()   : $this->emptyQueueStats(),
            'mt5Stats'     => $this->activeTab === 'mt5'     ? $this->getMt5Stats()     : $this->emptyMt5Stats(),
        ]);
    }
}
