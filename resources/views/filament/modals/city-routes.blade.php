<div class="space-y-4">
    @if($routes->count() > 0)
        <div class="space-y-3">
            @foreach($routes as $route)
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-base mb-2">
                                {{ $route->getTranslation('name', 'ar') }}
                            </h4>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">من:</span>
                                    <span class="font-medium">{{ $route->getTranslation('start_point_name', 'ar') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">إلى:</span>
                                    <span class="font-medium">{{ $route->getTranslation('end_point_name', 'ar') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">المسافة:</span>
                                    <span class="font-medium">{{ number_format($route->range_km, 2) }} كم</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">الحالة:</span>
                                    @if($route->is_active)
                                        <span class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">نشط</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full">غير نشط</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('filament.admin.resources.public-bus-routes.edit', $route) }}"
                           class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            عرض
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">لا توجد مسارات مرتبطة بهذه المدينة</p>
        </div>
    @endif
</div>
