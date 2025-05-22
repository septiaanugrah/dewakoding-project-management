<x-filament-panels::page>
    
    {{-- Project Selector --}}
    <div class="mb-6">
        <x-filament::section>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ $selectedProject ? $selectedProject->name : 'Select Project' }}
                </h2>
                
                <div class="w-full sm:w-auto">
                    <x-filament::input.wrapper>
                        <x-filament::input.select
                            wire:model.live="selectedProjectId"
                            class="w-full"
                        >
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $selectedProjectId == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>
    </div>

    @if($selectedProject)
        <div
            x-data="dragDropHandler()"
            x-init="init()"
            @ticket-moved.window="init()"
            @ticket-updated.window="init()"
            @refresh-board.window="init()"
            class="relative overflow-x-auto pb-6"
            id="board-container"
        >
            {{-- Scroll indicators --}}
            <div class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-gray-100 dark:from-gray-800 to-transparent pointer-events-none z-10"
                 x-data="{ visible: false }"
                 x-init="$nextTick(() => { 
                     const container = document.getElementById('board-container');
                     container.addEventListener('scroll', () => {
                         visible = container.scrollLeft > 20;
                     });
                 })"
                 x-show="visible"
                 x-transition.opacity
            ></div>
            
            <div class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-gray-100 dark:from-gray-800 to-transparent pointer-events-none z-10"
                 x-data="{ visible: false }"
                 x-init="$nextTick(() => { 
                     const container = document.getElementById('board-container');
                     visible = container.scrollWidth > container.clientWidth;
                     container.addEventListener('scroll', () => {
                         visible = container.scrollLeft + container.clientWidth < container.scrollWidth - 20;
                     });
                 })"
                 x-show="visible"
                 x-transition.opacity
            ></div>

            {{-- Mobile swipe hint --}}
            <div class="md:hidden flex justify-center mb-2 text-xs text-gray-500 dark:text-gray-400 items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Swipe horizontally to view all columns</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </div>

            <div class="inline-flex gap-4 pb-2 min-w-full">
                @foreach ($ticketStatuses as $status)
                    <div 
                        class="status-column rounded-xl border border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900"
                        style="width: calc(85vw - 2rem); min-width: 280px; max-width: 350px; @media (min-width: 640px) { width: calc((100vw - 6rem) / 2); } @media (min-width: 1024px) { width: calc((100vw - 8rem) / 3); } @media (min-width: 1280px) { width: calc((100vw - 10rem) / 4); }"
                        data-status-id="{{ $status->id }}"
                    >
                        <div 
                            class="px-4 py-3 rounded-t-xl border-b border-gray-200 dark:border-gray-700"
                            style="background-color: {{ $status->color ?? '#f3f4f6' }};"
                        >
                            <h3 class="font-medium flex items-center justify-between" style="color: white; text-shadow: 0px 0px 1px rgba(0,0,0,0.5);">
                                <span>{{ $status->name }}</span>
                                <span class="text-sm opacity-80">{{ $status->tickets->count() }}</span>
                            </h3>
                        </div>
                        
                        <div class="p-3 flex flex-col gap-3 h-[calc(100vh-22rem)] sm:h-[calc(100vh-20rem)] overflow-y-auto">
                            @foreach ($status->tickets as $ticket)
                                <div 
                                    class="ticket-card bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move"
                                    data-ticket-id="{{ $ticket->id }}"
                                >
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded truncate max-w-[120px] sm:max-w-none">
                                            {{ $ticket->uuid }}
                                        </span>
                                        @if ($ticket->due_date)
                                            <span class="text-xs px-1.5 py-0.5 rounded whitespace-nowrap {{ $ticket->due_date->isPast() ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }}">
                                                {{ $ticket->due_date->format('M d') }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-2">{{ $ticket->name }}</h4>
                                    
                                    @if ($ticket->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 100) }}
                                        </p>
                                    @endif
                                    
                                    <div class="flex justify-between items-center mt-2">
                                        @if ($ticket->assignee)
                                            <div class="inline-flex items-center px-2 py-1 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 gap-1 truncate">
                                                <span class="w-4 h-4 rounded-full bg-primary-500 flex items-center justify-center text-xs text-white flex-shrink-0">
                                                    {{ substr($ticket->assignee->name, 0, 1) }}
                                                </span>
                                                <span class="text-xs font-medium truncate">{{ $ticket->assignee->name }}</span>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 dark:text-gray-500 mr-1 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                </svg>
                                                <span class="text-xs font-medium">Unassigned</span>
                                            </div>
                                        @endif
                                        
                                        <button
                                            type="button" 
                                            wire:click="showTicketDetails({{ $ticket->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium rounded-lg border border-gray-200 dark:border-gray-700 text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400 flex-shrink-0"
                                        >
                                            <x-heroicon-m-eye class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            
                            @if ($status->tickets->isEmpty())
                                <div class="flex items-center justify-center h-24 text-gray-500 dark:text-gray-400 text-sm italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                    No tickets
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                
                @if ($ticketStatuses->isEmpty())
                    <div class="w-full flex items-center justify-center h-40 text-gray-500 dark:text-gray-400">
                        No status columns found for this project
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400 gap-4">
            <div class="flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 p-6">
                <x-heroicon-o-view-columns class="w-16 h-16 text-gray-400 dark:text-gray-500" />
            </div>
            <h2 class="text-xl font-medium text-gray-600 dark:text-gray-300">Please select a project first</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Select a project from the dropdown above to view the board
            </p>
        </div>
    @endif
    

    {{-- Drag and Drop Handler Script --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('dragDropHandler', () => ({
                draggingTicket: null,
                isTouchDevice: false,
                touchStartX: 0,
                touchStartY: 0,
                scrollStartX: 0,
                
                init() {
                    this.$nextTick(() => {
                        this.removeAllEventListeners();
                        this.attachAllEventListeners();
                        this.setupTouchScrolling();
                        this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                    });
                },
                
                setupTouchScrolling() {
                    const container = document.getElementById('board-container');
                    
                    container.addEventListener('touchstart', (e) => {
                        this.touchStartX = e.touches[0].clientX;
                        this.touchStartY = e.touches[0].clientY;
                        this.scrollStartX = container.scrollLeft;
                    }, { passive: true });
                    
                    container.addEventListener('touchmove', (e) => {
                        if (e.touches.length !== 1) return;
                        
                        const touchX = e.touches[0].clientX;
                        const touchY = e.touches[0].clientY;
                        
                        // Calculate both horizontal and vertical movement
                        const moveX = this.touchStartX - touchX;
                        const moveY = this.touchStartY - touchY;
                        
                        // If horizontal movement is greater than vertical movement, prevent default scrolling
                        if (Math.abs(moveX) > Math.abs(moveY)) {
                            e.preventDefault();
                            container.scrollLeft = this.scrollStartX + moveX;
                        }
                    }, { passive: false });
                },
                
                removeAllEventListeners() {
                    const tickets = document.querySelectorAll('.ticket-card');
                    tickets.forEach(ticket => {
                        ticket.removeAttribute('draggable');
                        const newTicket = ticket.cloneNode(true);
                        ticket.parentNode.replaceChild(newTicket, ticket);
                    });
                    
                    const columns = document.querySelectorAll('.status-column');
                    columns.forEach(column => {
                        const newColumn = column.cloneNode(false);
                        
                        while (column.firstChild) {
                            newColumn.appendChild(column.firstChild);
                        }
                        
                        if (column.parentNode) {
                            column.parentNode.replaceChild(newColumn, column);
                        }
                    });
                },
                
                attachAllEventListeners() {
                    const tickets = document.querySelectorAll('.ticket-card');
                    tickets.forEach(ticket => {
                        ticket.setAttribute('draggable', true);
                        
                        ticket.addEventListener('dragstart', (e) => {
                            this.draggingTicket = ticket.getAttribute('data-ticket-id');
                            ticket.classList.add('opacity-50');
                            e.dataTransfer.effectAllowed = 'move';
                        });
                        
                        ticket.addEventListener('dragend', () => {
                            ticket.classList.remove('opacity-50');
                            this.draggingTicket = null;
                        });
                        
                        // Touch events for mobile drag and drop
                        let longPressTimer;
                        let isDragging = false;
                        let originalColumn;
                        
                        ticket.addEventListener('touchstart', (e) => {
                            // Only proceed if not already scrolling
                            if (isDragging) return;
                            
                            longPressTimer = setTimeout(() => {
                                originalColumn = ticket.closest('.status-column');
                                this.draggingTicket = ticket.getAttribute('data-ticket-id');
                                ticket.classList.add('opacity-50', 'relative', 'z-30');
                                isDragging = true;
                                
                                // Visual feedback
                                ticket.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)';
                            }, 500); // 500ms long press
                        }, { passive: true });
                        
                        ticket.addEventListener('touchmove', (e) => {
                            if (!isDragging) {
                                // If not dragging, clear the timer to prevent entering drag mode
                                clearTimeout(longPressTimer);
                                return;
                            }
                            
                            // Move the ticket with touch
                            const touch = e.touches[0];
                            const columns = document.querySelectorAll('.status-column');
                            
                            // Find column under touch position
                            let targetColumn = null;
                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left && 
                                    touch.clientX <= rect.right && 
                                    touch.clientY >= rect.top && 
                                    touch.clientY <= rect.bottom) {
                                    targetColumn = column;
                                    column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                                } else {
                                    column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                                }
                            });
                        });
                        
                        ticket.addEventListener('touchend', (e) => {
                            clearTimeout(longPressTimer);
                            
                            if (!isDragging) return;
                            
                            isDragging = false;
                            ticket.classList.remove('opacity-50', 'relative', 'z-30');
                            ticket.style.boxShadow = '';
                            
                            // Find column under final touch position
                            const touch = e.changedTouches[0];
                            const columns = document.querySelectorAll('.status-column');
                            
                            let targetColumn = null;
                            columns.forEach(column => {
                                const rect = column.getBoundingClientRect();
                                if (touch.clientX >= rect.left && 
                                    touch.clientX <= rect.right && 
                                    touch.clientY >= rect.top && 
                                    touch.clientY <= rect.bottom) {
                                    targetColumn = column;
                                }
                                column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                            });
                            
                            if (targetColumn && targetColumn !== originalColumn) {
                                const statusId = targetColumn.getAttribute('data-status-id');
                                const ticketId = this.draggingTicket;
                                
                                const componentId = document.querySelector('[wire\\:id]').getAttribute('wire:id');
                                if (componentId) {
                                    Livewire.find(componentId).moveTicket(
                                        parseInt(ticketId), 
                                        parseInt(statusId)
                                    );
                                }
                            }
                            
                            this.draggingTicket = null;
                        });
                        
                        ticket.addEventListener('touchcancel', () => {
                            clearTimeout(longPressTimer);
                            if (!isDragging) return;
                            
                            isDragging = false;
                            ticket.classList.remove('opacity-50', 'relative', 'z-30');
                            ticket.style.boxShadow = '';
                            this.draggingTicket = null;
                            
                            document.querySelectorAll('.status-column').forEach(column => {
                                column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                            });
                        });
                        
                        const detailsButton = ticket.querySelector('button');
                        if (detailsButton) {
                            const ticketId = ticket.getAttribute('data-ticket-id');
                            detailsButton.addEventListener('click', (e) => {
                                e.stopPropagation(); // Prevent triggering parent events
                                const componentId = document.querySelector('[wire\\:id]').getAttribute('wire:id');
                                if (componentId) {
                                    Livewire.find(componentId).showTicketDetails(ticketId);
                                }
                            });
                        }
                    });
                    
                    const columns = document.querySelectorAll('.status-column');
                    columns.forEach(column => {
                        column.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            column.classList.add('bg-primary-50', 'dark:bg-primary-950');
                        });
                        
                        column.addEventListener('dragleave', () => {
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                        });
                        
                        column.addEventListener('drop', (e) => {
                            e.preventDefault();
                            column.classList.remove('bg-primary-50', 'dark:bg-primary-950');
                            
                            if (this.draggingTicket) {
                                const statusId = column.getAttribute('data-status-id');
                                const ticketId = this.draggingTicket;
                                this.draggingTicket = null;
                                
                                const componentId = document.querySelector('[wire\\:id]').getAttribute('wire:id');
                                if (componentId) {
                                    Livewire.find(componentId).moveTicket(
                                        parseInt(ticketId), 
                                        parseInt(statusId)
                                    );
                                }
                            }
                        });
                    });
                }
            }));
        });
    </script>

    {{-- Add meta viewport tag if not already present --}}
    <script>
        // Ensure proper viewport meta tag exists
        document.addEventListener('DOMContentLoaded', () => {
            let viewportMeta = document.querySelector('meta[name="viewport"]');
            if (!viewportMeta) {
                viewportMeta = document.createElement('meta');
                viewportMeta.name = 'viewport';
                document.head.appendChild(viewportMeta);
            }
            viewportMeta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
            
        });
    </script>
</x-filament-panels::page>