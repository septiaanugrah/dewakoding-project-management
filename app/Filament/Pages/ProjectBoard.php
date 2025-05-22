<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class ProjectBoard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static string $view = 'filament.pages.project-board';

    protected static ?string $title = 'Project Board';

    protected static ?string $navigationLabel = 'Project Board';

    protected static ?string $navigationGroup = 'Project Visualization';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'project-board/{project_id?}';

    public ?Project $selectedProject = null;

    public Collection $projects;

    public Collection $ticketStatuses;

    public ?Ticket $selectedTicket = null;

    public ?int $selectedProjectId = null;

    public function mount($project_id = null): void
    {
        if (auth()->user()->hasRole(['super_admin'])) {
            $this->projects = Project::all();
        } else {
            $this->projects = auth()->user()->projects;
        }

        if ($project_id && $this->projects->contains('id', $project_id)) {
            $this->selectedProjectId = (int) $project_id;
            $this->selectedProject = Project::find($project_id);
            $this->loadTicketStatuses();
        } elseif ($this->projects->isNotEmpty() && ! is_null($project_id)) {
            Notification::make()
                ->title('Project Not Found')
                ->danger()
                ->send();
            $this->redirect(static::getUrl());
        }
    }

    public function selectProject(int $projectId): void
    {
        $this->selectedTicket = null;
        $this->ticketStatuses = collect();
        $this->selectedProjectId = $projectId;
        $this->selectedProject = Project::find($projectId);

        if ($this->selectedProject) {
            $url = static::getUrl(['project_id' => $projectId]);
            $this->redirect($url);

            $this->loadTicketStatuses();
        }
    }

    public function updatedSelectedProjectId($value): void
    {
        if ($value) {
            $this->selectProject((int) $value);
        } else {
            $this->selectedProject = null;
            $this->ticketStatuses = collect();

            $this->redirect(static::getUrl());
        }
    }

    public function loadTicketStatuses(): void
    {
        if (! $this->selectedProject) {
            $this->ticketStatuses = collect();

            return;
        }

        $this->ticketStatuses = $this->selectedProject->ticketStatuses()
            ->with(['tickets' => function ($query) {
                $query->with(['assignee', 'status'])
                    ->orderBy('created_at', 'desc');
            }])
            ->orderBy('sort_order')
            ->get();
    }

    #[On('ticket-moved')]
    public function moveTicket($ticketId, $newStatusId): void
    {
        $ticket = Ticket::find($ticketId);

        if ($ticket && $ticket->project_id === $this->selectedProject?->id) {
            $ticket->update([
                'ticket_status_id' => $newStatusId,
            ]);

            $this->loadTicketStatuses();

            $this->dispatch('ticket-updated');

            Notification::make()
                ->title('Ticket Updated')
                ->success()
                ->send();
        }
    }

    #[On('refresh-board')]
    public function refreshBoard(): void
    {
        $this->loadTicketStatuses();
        $this->dispatch('ticket-updated');
    }

    public function showTicketDetails(int $ticketId): void
    {
        $ticket = Ticket::with(['assignee', 'status', 'project'])->find($ticketId);

        if (! $ticket) {
            Notification::make()
                ->title('Ticket Not Found')
                ->danger()
                ->send();

            return;
        }

        
        $url = TicketResource::getUrl('view', ['record' => $ticketId]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function closeTicketDetails(): void
    {
        $this->selectedTicket = null;
    }

    public function editTicket(int $ticketId): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $this->canEditTicket($ticket)) {
            Notification::make()
                ->title('Permission Denied')
                ->body('You do not have permission to edit this ticket.')
                ->danger()
                ->send();

            return;
        }

        $this->redirect(TicketResource::getUrl('edit', ['record' => $ticketId]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->label('New Ticket')
                ->icon('heroicon-m-plus')
                ->visible(fn () => $this->selectedProject !== null && auth()->user()->hasRole(['super_admin']))
                ->url(fn (): string => TicketResource::getUrl('create', [
                    'project_id' => $this->selectedProject?->id,
                    'ticket_status_id' => $this->selectedProject?->ticketStatuses->first()?->id,
                ])),

            Action::make('refresh_board')
                ->label('Refresh Board')
                ->icon('heroicon-m-arrow-path')
                ->action('refreshBoard'),
        ];
    }

    private function canViewTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canEditTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }

    private function canManageTicket(?Ticket $ticket): bool
    {
        if (! $ticket) {
            return false;
        }

        return auth()->user()->hasRole(['super_admin'])
            || $ticket->user_id === auth()->id();
    }
}
