<x-filament-widgets::widget>
    <x-filament::section heading="Project Timeline">
        <div class="space-y-4">
            
            @if(count($projects) === 0)
                <div class="p-4 text-center text-gray-500">
                    <p class="text-sm">No projects available</p>
                    <p class="text-xs">Please check back later.</p>
                </div>
            @else
                <div class="space-y-6 mt-4">
                    @foreach($projects as $project)
                        <div class="space-y-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-lg">{{ $project['name'] }}</h3>
                                </div>
                                
                                <div class="text-right text-sm text-gray-500">
                                {{ $project['start_date'] }} - {{ $project['end_date'] }} | {{ $project['progress_percent'] }}% completed 
                                </div>
                            </div>
                            
                            <div class="relative w-full h-10 bg-gray-200 rounded-lg overflow-hidden">
                                <div class="absolute top-0 left-0 h-full"
                                    style="width: {{ $project['progress_percent'] }}%; background-color: #1d4ed8;">
                                </div>
                                
                                @if($project['remaining_days'] > 0)
                                    <div class="absolute top-0 h-full bg-gray-300"
                                        style="left: {{ $project['progress_percent'] }}%; width: {{ 100 - $project['progress_percent'] }}%;">
                                    </div>
                                @endif
                                
                                <div class="absolute inset-0 flex items-center px-4 text-sm font-medium">
                                    @if($project['progress_percent'] > 0)
                                        <span class="mr-2 text-white drop-shadow-sm">{{ $project['past_days'] }} days passed</span>
                                    @endif
                                    
                                    @if($project['remaining_days'] > 0)
                                        <span class="ml-auto text-gray-700">{{ $project['remaining_days'] }} days remaining</span>
                                    @elseif($project['remaining_days'] <= 0 && $project['progress_percent'] < 100)
                                        <span class="ml-auto text-red-700">{{ abs($project['remaining_days']) }} days overdue</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>