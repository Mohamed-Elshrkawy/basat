<x-filament-panels::page>
    <div class="space-y-4">
        <!-- شريط الأدوات -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">إدارة المحطات على الخريطة</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">انقر على الخريطة لإضافة محطة جديدة</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        عدد المحطات: <span id="stops-count">{{ $this->getStops()->count() }}</span>
                    </span>
                </div>
            </div>

            <!-- تعليمات -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-blue-900 dark:text-blue-200 mb-1">كيفية الاستخدام:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                            <li><strong>إضافة محطة:</strong> انقر على أي مكان داخل السعودية</li>
                            <li><strong>عرض معلومات:</strong> مرر الماوس على علامة المحطة لعرض الاسم</li>
                            <li><strong>تعديل محطة:</strong> انقر على علامة المحطة ثم اختر "تعديل"</li>
                            <li><strong>نقل محطة:</strong> اسحب علامة المحطة للمكان الجديد</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden" wire:ignore>
            <div id="stops-map" style="height: 650px; width: 100%;"></div>
        </div>

        <!-- أسطورة الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold mb-3">دليل الخريطة</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-2">
                    <div style="width: 30px; height: 30px;">
                        <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; fill: #3b82f6;">
                            <rect x="3" y="6" width="18" height="14" rx="2" fill="currentColor"/>
                            <rect x="6" y="9" width="4" height="8" rx="1" fill="white"/>
                            <rect x="14" y="9" width="4" height="8" rx="1" fill="white"/>
                            <circle cx="12" cy="13" r="1.5" fill="white"/>
                        </svg>
                    </div>
                    <span class="text-sm">محطة نشطة</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 30px; height: 30px;">
                        <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; fill: #9ca3af;">
                            <rect x="3" y="6" width="18" height="14" rx="2" fill="currentColor"/>
                            <rect x="6" y="9" width="4" height="8" rx="1" fill="white"/>
                            <rect x="14" y="9" width="4" height="8" rx="1" fill="white"/>
                            <circle cx="12" cy="13" r="1.5" fill="white"/>
                        </svg>
                    </div>
                    <span class="text-sm">محطة غير نشطة</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-sm">انقر لإضافة محطة</span>
                </div>
            </div>
        </div>

        <!-- Modal لإضافة/تعديل محطة -->
        <div id="stop-modal" class="hidden fixed inset-0 overflow-y-auto" style="z-index: 99999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" style="z-index: 99998;" onclick="closeStopModal()"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6 transform transition-all" style="z-index: 99999;">
                    <h3 class="text-lg font-semibold mb-4" id="modal-title">إضافة محطة جديدة</h3>

                    <div class="space-y-4">
                        <!-- اسم المحطة بالعربي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المحطة (عربي) *</label>
                            <input type="text"
                                   id="stop-name-ar"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="مثال: محطة الراحة الأولى"
                                   autofocus>
                        </div>

                        <!-- اسم المحطة بالإنجليزي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المحطة (English) *</label>
                            <input type="text"
                                   id="stop-name-en"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="Example: First Rest Stop">
                        </div>

                        <!-- الإحداثيات (للعرض فقط) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-2">خط العرض</label>
                                <input type="text"
                                       id="stop-lat"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">خط الطول</label>
                                <input type="text"
                                       id="stop-lng"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                        </div>

                        <!-- حالة المحطة -->
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                   id="stop-is-active"
                                   checked
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="stop-is-active" class="text-sm font-medium">محطة نشطة</label>
                        </div>

                        <input type="hidden" id="editing-stop-id">
                    </div>

                    <!-- الأزرار -->
                    <div class="flex gap-2 justify-end mt-6">
                        <button onclick="closeStopModal()"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                            إلغاء
                        </button>
                        <button onclick="saveStop()"
                                id="save-stop-btn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            حفظ
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            let map;
            let stopMarkers = {};
            let tempMarker = null;
            let stops = @json($this->getStops());

            // حدود السعودية
            const saudiBounds = [
                [16.0, 34.0],
                [32.5, 56.0]
            ];

            // تهيئة الخريطة
            function initMap() {
                if (map) {
                    console.log('Map already initialized');
                    return;
                }

                map = L.map('stops-map', {
                    maxBounds: saudiBounds,
                    maxBoundsViscosity: 1.0,
                    minZoom: 5,
                    maxZoom: 18
                }).setView([24.0, 45.0], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);

                map.fitBounds(saudiBounds);

                console.log('Map initialized');
                console.log('Stops:', stops);

                displayAllStops();

                // عند النقر على الخريطة
                map.on('click', (e) => {
                    if (e.latlng.lat >= 16.0 && e.latlng.lat <= 32.5 &&
                        e.latlng.lng >= 34.0 && e.latlng.lng <= 56.0) {
                        openAddStopModal(e.latlng.lat, e.latlng.lng);
                    }
                });
            }

            // عرض جميع المحطات
            function displayAllStops() {
                console.log('Displaying stops:', stops.length);

                stops.forEach((stop, index) => {
                    console.log(`Adding stop ${index + 1}:`, stop.name.ar);
                    addStopMarker(stop);
                });

                console.log('All stops displayed');
            }

            // إضافة marker للمحطة
            function addStopMarker(stop) {
                const color = stop.is_active ? '#3b82f6' : '#9ca3af';

                const icon = L.divIcon({
                    html: `
        <div style="position: relative; width: 40px; height: 40px;">
            <svg viewBox="0 0 64 64" style="width: 100%; height: 100%;">
                <!-- ظل خفيف -->
                <ellipse cx="32" cy="60" rx="10" ry="3" fill="rgba(0,0,0,0.25)" />

                <!-- العصا -->
                <rect x="30.5" y="12" width="3" height="38" rx="1.5" fill="#555" />

                <!-- العلم -->
                <path d="M33 14
                         L52 18
                         C54 18.5,54 22,52 22.5
                         L33 26
                         Z"
                      fill="url(#flagGradient)"
                      stroke="#d32f2f"
                      stroke-width="1.5"
                      stroke-linejoin="round"/>

                <!-- رأس العلم (نقطة صغيرة فوق) -->
                <circle cx="32" cy="12" r="2" fill="#d32f2f" />

                <defs>
                    <linearGradient id="flagGradient" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#ff5f6d" />
                        <stop offset="100%" stop-color="#d32f2f" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
    `,
                    className: "",
                    iconSize: [40, 40],
                    iconAnchor: [20, 40],
                    popupAnchor: [0, -38]
                });
                const marker = L.marker([parseFloat(stop.lat), parseFloat(stop.lng)], {
                    icon: icon,
                    draggable: true,
                    title: stop.name.ar
                }).addTo(map);

                marker.bindTooltip(stop.name.ar, {
                    permanent: false,
                    direction: 'top',
                    offset: [0, -10],
                    className: 'custom-tooltip'
                });

                marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    updateStopLocation(stop.id, position.lat, position.lng);
                });

                const popupContent = `
                <div style="min-width: 220px;">
                    <h3 style="font-weight: bold; margin-bottom: 8px; font-size: 17px; color: #1f2937;">🚏 ${stop.name.ar}</h3>
                    <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">${stop.name.en}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: #9ca3af;">📍 ${parseFloat(stop.lat).toFixed(4)}, ${parseFloat(stop.lng).toFixed(4)}</p>
                    <p style="margin: 10px 0 12px 0; font-size: 13px;">
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-weight: 500; ${stop.is_active ? 'background: #dbeafe; color: #1e40af;' : 'background: #f3f4f6; color: #6b7280;'}">
                            ${stop.is_active ? '✓ نشطة' : '✗ غير نشطة'}
                        </span>
                    </p>
                    <div style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button onclick="openEditStopModal(${stop.id})"
                                style="padding: 8px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: background 0.2s;">
                            ✏️ تعديل
                        </button>
                        <button onclick="confirmDeleteStop(${stop.id})"
                                style="padding: 8px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: background 0.2s;">
                            🗑️ حذف
                        </button>
                    </div>
                </div>
            `;

                marker.bindPopup(popupContent, {
                    maxWidth: 250,
                    className: 'custom-popup'
                });

                stopMarkers[stop.id] = marker;
            }

            // فتح modal لإضافة محطة
            function openAddStopModal(lat, lng) {
                document.getElementById('modal-title').textContent = 'إضافة محطة جديدة';
                document.getElementById('stop-name-ar').value = '';
                document.getElementById('stop-name-en').value = '';
                document.getElementById('stop-lat').value = lat.toFixed(6);
                document.getElementById('stop-lng').value = lng.toFixed(6);
                document.getElementById('stop-is-active').checked = true;
                document.getElementById('editing-stop-id').value = '';
                document.getElementById('save-stop-btn').textContent = 'إضافة المحطة';

                // marker مؤقت
                if (tempMarker) {
                    map.removeLayer(tempMarker);
                }

                const tempIcon = L.divIcon({
                    html: `
                    <div style="position: relative; width: 40px; height: 40px; animation: bounce 0.6s infinite;">
                        <svg viewBox="0 0 24 24" style="width: 100%; height: 100%; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.5));">
                            <circle cx="12" cy="12" r="11" fill="#10b981"/>
                            <text x="12" y="16" text-anchor="middle" fill="white" font-size="16" font-weight="bold">+</text>
                            <circle cx="12" cy="12" r="11" fill="none" stroke="white" stroke-width="2"/>
                        </svg>
                    </div>
                `,
                    className: '',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                });

                tempMarker = L.marker([lat, lng], { icon: tempIcon }).addTo(map);

                document.getElementById('stop-modal').classList.remove('hidden');

                setTimeout(() => {
                    document.getElementById('stop-name-ar').focus();
                }, 100);
            }

            // فتح modal لتعديل محطة
            function openEditStopModal(stopId) {
                const stop = stops.find(s => s.id === stopId);
                if (!stop) {
                    console.error('Stop not found:', stopId);
                    return;
                }

                document.getElementById('modal-title').textContent = 'تعديل المحطة';
                document.getElementById('stop-name-ar').value = stop.name.ar;
                document.getElementById('stop-name-en').value = stop.name.en;
                document.getElementById('stop-lat').value = parseFloat(stop.lat).toFixed(6);
                document.getElementById('stop-lng').value = parseFloat(stop.lng).toFixed(6);
                document.getElementById('stop-is-active').checked = stop.is_active;
                document.getElementById('editing-stop-id').value = stop.id;
                document.getElementById('save-stop-btn').textContent = 'حفظ التعديلات';

                document.getElementById('stop-modal').classList.remove('hidden');

                setTimeout(() => {
                    document.getElementById('stop-name-ar').focus();
                }, 100);
            }

            // إغلاق modal
            function closeStopModal() {
                document.getElementById('stop-modal').classList.add('hidden');

                if (tempMarker) {
                    map.removeLayer(tempMarker);
                    tempMarker = null;
                }
            }

            // حفظ المحطة
            async function saveStop() {
                const nameAr = document.getElementById('stop-name-ar').value.trim();
                const nameEn = document.getElementById('stop-name-en').value.trim();
                const lat = parseFloat(document.getElementById('stop-lat').value);
                const lng = parseFloat(document.getElementById('stop-lng').value);
                const isActive = document.getElementById('stop-is-active').checked;
                const editingId = document.getElementById('editing-stop-id').value;

                if (!nameAr || !nameEn) {
                    alert('يجب إدخال اسم المحطة بالعربي والإنجليزي');
                    return;
                }

                const data = {
                    name_ar: nameAr,
                    name_en: nameEn,
                    lat: lat,
                    lng: lng,
                    is_active: isActive
                };

                try {
                    let result;

                    if (editingId) {
                        result = await Livewire.find('{{ $this->getId() }}').call('updateStop', editingId, data);

                        if (result) {
                            const stopIndex = stops.findIndex(s => s.id == editingId);
                            if (stopIndex !== -1) {
                                stops[stopIndex] = result;

                                if (stopMarkers[result.id]) {
                                    map.removeLayer(stopMarkers[result.id]);
                                }
                                addStopMarker(result);
                            }
                        }
                    } else {
                        result = await Livewire.find('{{ $this->getId() }}').call('createStop', data);

                        if (result) {
                            stops.push(result);
                            addStopMarker(result);
                            document.getElementById('stops-count').textContent = stops.length;
                        }
                    }

                    closeStopModal();
                } catch (error) {
                    console.error('Error saving stop:', error);
                    alert('حدث خطأ أثناء الحفظ');
                }
            }

            // تحديث موقع المحطة
            async function updateStopLocation(stopId, lat, lng) {
                try {
                    await Livewire.find('{{ $this->getId() }}').call('updateStopLocation', stopId, lat, lng);

                    const stop = stops.find(s => s.id === stopId);
                    if (stop) {
                        stop.lat = lat;
                        stop.lng = lng;
                    }
                } catch (error) {
                    console.error('Error updating location:', error);
                }
            }

            // تأكيد حذف المحطة
            function confirmDeleteStop(stopId) {
                const stop = stops.find(s => s.id === stopId);
                const stopName = stop ? stop.name.ar : 'المحطة';

                if (confirm(`هل أنت متأكد من حذف "${stopName}"؟\n\nسيتم حذف جميع البيانات المرتبطة بها.`)) {
                    deleteStop(stopId);
                }
            }

            // حذف المحطة
            async function deleteStop(stopId) {
                try {
                    const result = await Livewire.find('{{ $this->getId() }}').call('deleteStop', stopId);

                    if (result) {
                        stops = stops.filter(s => s.id !== stopId);

                        if (stopMarkers[stopId]) {
                            map.removeLayer(stopMarkers[stopId]);
                            delete stopMarkers[stopId];
                        }

                        document.getElementById('stops-count').textContent = stops.length;
                    }
                } catch (error) {
                    console.error('Error deleting stop:', error);
                }
            }

            // إغلاق Modal بالضغط على Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeStopModal();
                }
            });

            // تهيئة الخريطة
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded, initializing map...');
                initMap();
            });

            // إعادة تهيئة بعد Livewire updates
            Livewire.hook('morph.updated', ({ el, component }) => {
                const mapElement = document.getElementById('stops-map');
                if (mapElement && !mapElement._leaflet_id) {
                    console.log('Re-initializing stops map...');
                    setTimeout(() => {
                        initMap();
                    }, 100);
                } else if (map) {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                }
            });
        </script>

        <style>
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }

            .custom-tooltip {
                background: rgba(0, 0, 0, 0.85) !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 12px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: white !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            }

            .custom-tooltip::before {
                border-top-color: rgba(0, 0, 0, 0.85) !important;
            }

            .custom-popup .leaflet-popup-content-wrapper {
                border-radius: 10px !important;
                padding: 8px !important;
            }

            .custom-popup .leaflet-popup-content {
                margin: 8px !important;
            }

            .dark .custom-popup .leaflet-popup-content-wrapper {
                background: #1f2937 !important;
                color: white !important;
            }

            .dark .custom-popup .leaflet-popup-tip {
                background: #1f2937 !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
