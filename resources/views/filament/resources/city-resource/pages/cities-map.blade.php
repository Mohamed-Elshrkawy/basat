<x-filament-panels::page>
    <div class="space-y-4">
        <!-- شريط الأدوات -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">إدارة المدن على الخريطة</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">انقر على الخريطة لإضافة مدينة جديدة</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        عدد المدن: <span id="cities-count">{{ $this->getCities()->count() }}</span>
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
                            <li><strong>إضافة مدينة:</strong> انقر على أي مكان داخل السعودية</li>
                            <li><strong>عرض معلومات:</strong> مرر الماوس على علامة المدينة لعرض الاسم</li>
                            <li><strong>تعديل مدينة:</strong> انقر على علامة المدينة ثم اختر "تعديل"</li>
                            <li><strong>نقل مدينة:</strong> اسحب علامة المدينة للمكان الجديد</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden" wire:ignore>
            <div id="cities-map" style="height: 650px; width: 100%;"></div>
        </div>

        <!-- أسطورة الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold mb-3">دليل الخريطة</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px; position: relative;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #ef4444;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm">مدينة نشطة</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px; position: relative;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #9ca3af;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm">مدينة غير نشطة</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-sm">انقر لإضافة مدينة</span>
                </div>
            </div>
        </div>

        <!-- Modal لإضافة/تعديل مدينة -->
        <div id="city-modal" class="hidden fixed inset-0 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" onclick="closeCityModal()"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6 transform transition-all" style="z-index: 10000;">
                    <h3 class="text-lg font-semibold mb-4" id="modal-title">إضافة مدينة جديدة</h3>

                    <div class="space-y-4">
                        <!-- اسم المدينة بالعربي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المدينة (عربي) *</label>
                            <input type="text"
                                   id="city-name-ar"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="مثال: الرياض"
                                   autofocus>
                        </div>

                        <!-- اسم المدينة بالإنجليزي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المدينة (English) *</label>
                            <input type="text"
                                   id="city-name-en"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="Example: Riyadh">
                        </div>

                        <!-- الإحداثيات (للعرض فقط) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-2">خط العرض</label>
                                <input type="text"
                                       id="city-lat"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">خط الطول</label>
                                <input type="text"
                                       id="city-lng"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                        </div>

                        <!-- حالة المدينة -->
                        <div class="flex items-center gap-2">
                            <input type="checkbox"
                                   id="city-is-active"
                                   checked
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="city-is-active" class="text-sm font-medium">مدينة نشطة</label>
                        </div>

                        <input type="hidden" id="editing-city-id">
                    </div>

                    <!-- الأزرار -->
                    <div class="flex gap-2 justify-end mt-6">
                        <button onclick="closeCityModal()"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                            إلغاء
                        </button>
                        <button onclick="saveCity()"
                                id="save-city-btn"
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
            let cityMarkers = {};
            let tempMarker = null;
            let cities = @json($this->getCities());

            // حدود السعودية
            const saudiBounds = [
                [16.0, 34.0],  // الجنوب الغربي
                [32.5, 56.0]   // الشمال الشرقي
            ];

            // تهيئة الخريطة
            function initMap() {
                // التحقق من أن الخريطة غير موجودة بالفعل
                if (map) {
                    console.log('Map already initialized');
                    return;
                }

                // إنشاء الخريطة مع تحديد الحدود
                map = L.map('cities-map', {
                    maxBounds: saudiBounds,
                    maxBoundsViscosity: 1.0,
                    minZoom: 5,
                    maxZoom: 13
                }).setView([24.0, 45.0], 6);

                // إضافة طبقة الخريطة
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);

                // تحديد الخريطة للسعودية فقط
                map.fitBounds(saudiBounds);

                console.log('Map initialized with Saudi Arabia bounds');
                console.log('Cities:', cities);

                // عرض كل المدن
                displayAllCities();

                // عند النقر على الخريطة (لإضافة مدينة جديدة)
                map.on('click', (e) => {
                    // التحقق من أن النقر داخل حدود السعودية
                    if (e.latlng.lat >= 16.0 && e.latlng.lat <= 32.5 &&
                        e.latlng.lng >= 34.0 && e.latlng.lng <= 56.0) {
                        openAddCityModal(e.latlng.lat, e.latlng.lng);
                    }
                });
            }

            // عرض جميع المدن
            function displayAllCities() {
                console.log('Displaying cities:', cities.length);

                cities.forEach((city, index) => {
                    console.log(`Adding city ${index + 1}:`, city.name.ar);
                    addCityMarker(city);
                });

                console.log('All cities displayed');
            }

            // إضافة marker للمدينة
            function addCityMarker(city) {
                const color = city.is_active ? '#ef4444' : '#9ca3af'; // أحمر للنشط، رمادي لغير النشط

                // أيقونة دبوس كلاسيكية
                const icon = L.divIcon({
                    html: `
                    <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                        <path fill="${color}" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        <circle cx="192" cy="192" r="60" fill="white"/>
                    </svg>
                `,
                    className: '',
                    iconSize: [30, 49],
                    iconAnchor: [15, 49],
                    popupAnchor: [0, -49],
                    tooltipAnchor: [0, -49]
                });

                const marker = L.marker([city.lat, city.lng], {
                    icon: icon,
                    draggable: true,
                    title: city.name.ar // اسم المدينة عند التمرير
                }).addTo(map);

                // Tooltip يظهر عند التمرير على المدينة
                marker.bindTooltip(city.name.ar, {
                    permanent: false,
                    direction: 'right',
                    offset: [20, -20],
                    className: 'custom-tooltip'
                });

                // عند سحب الـ marker
                marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    updateCityLocation(city.id, position.lat, position.lng);
                });

                // Popup للمدينة
                const popupContent = `
                <div style="min-width: 220px;">
                    <h3 style="font-weight: bold; margin-bottom: 8px; font-size: 17px; color: #1f2937;">${city.name.ar}</h3>
                    <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">${city.name.en}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: #9ca3af;">📍 ${parseFloat(city.lat).toFixed(4)}, ${parseFloat(city.lng).toFixed(4)}</p>
                    <p style="margin: 10px 0 12px 0; font-size: 13px;">
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-weight: 500; ${city.is_active ? 'background: #dcfce7; color: #166534;' : 'background: #f3f4f6; color: #6b7280;'}">
                            ${city.is_active ? '✓ نشط' : '✗ غير نشط'}
                        </span>
                    </p>
                    <div style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button onclick="openEditCityModal(${city.id})"
                                style="padding: 8px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; transition: background 0.2s;">
                            ✏️ تعديل
                        </button>
                        <button onclick="confirmDeleteCity(${city.id})"
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

                // حفظ الـ marker
                cityMarkers[city.id] = marker;
            }

            // فتح modal لإضافة مدينة
            function openAddCityModal(lat, lng) {
                document.getElementById('modal-title').textContent = 'إضافة مدينة جديدة';
                document.getElementById('city-name-ar').value = '';
                document.getElementById('city-name-en').value = '';
                document.getElementById('city-lat').value = lat.toFixed(6);
                document.getElementById('city-lng').value = lng.toFixed(6);
                document.getElementById('city-is-active').checked = true;
                document.getElementById('editing-city-id').value = '';
                document.getElementById('save-city-btn').textContent = 'إضافة المدينة';

                // إضافة marker مؤقت
                if (tempMarker) {
                    map.removeLayer(tempMarker);
                }

                const tempIcon = L.divIcon({
                    html: `
                    <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4)); animation: bounce 0.6s infinite;">
                        <path fill="#10b981" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        <text x="192" y="210" text-anchor="middle" fill="white" font-size="140" font-weight="bold">+</text>
                    </svg>
                `,
                    className: '',
                    iconSize: [30, 49],
                    iconAnchor: [15, 49]
                });

                tempMarker = L.marker([lat, lng], { icon: tempIcon }).addTo(map);

                document.getElementById('city-modal').classList.remove('hidden');

                // Focus على حقل الاسم
                setTimeout(() => {
                    document.getElementById('city-name-ar').focus();
                }, 100);
            }

            // فتح modal لتعديل مدينة
            function openEditCityModal(cityId) {
                const city = cities.find(c => c.id === cityId);
                if (!city) {
                    console.error('City not found:', cityId);
                    return;
                }

                document.getElementById('modal-title').textContent = 'تعديل المدينة';
                document.getElementById('city-name-ar').value = city.name.ar;
                document.getElementById('city-name-en').value = city.name.en;
                document.getElementById('city-lat').value = parseFloat(city.lat).toFixed(6);
                document.getElementById('city-lng').value = parseFloat(city.lng).toFixed(6);
                document.getElementById('city-is-active').checked = city.is_active;
                document.getElementById('editing-city-id').value = city.id;
                document.getElementById('save-city-btn').textContent = 'حفظ التعديلات';

                document.getElementById('city-modal').classList.remove('hidden');

                // Focus على حقل الاسم
                setTimeout(() => {
                    document.getElementById('city-name-ar').focus();
                }, 100);
            }

            // إغلاق modal
            function closeCityModal() {
                document.getElementById('city-modal').classList.add('hidden');

                // حذف الـ marker المؤقت
                if (tempMarker) {
                    map.removeLayer(tempMarker);
                    tempMarker = null;
                }
            }

            // حفظ المدينة
            async function saveCity() {
                const nameAr = document.getElementById('city-name-ar').value.trim();
                const nameEn = document.getElementById('city-name-en').value.trim();
                const lat = parseFloat(document.getElementById('city-lat').value);
                const lng = parseFloat(document.getElementById('city-lng').value);
                const isActive = document.getElementById('city-is-active').checked;
                const editingId = document.getElementById('editing-city-id').value;

                if (!nameAr || !nameEn) {
                    alert('يجب إدخال اسم المدينة بالعربي والإنجليزي');
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
                        // تحديث مدينة موجودة
                        result = await Livewire.find('{{ $this->getId() }}').call('updateCity', editingId, data);

                        if (result) {
                            // تحديث البيانات محلياً
                            const cityIndex = cities.findIndex(c => c.id == editingId);
                            if (cityIndex !== -1) {
                                cities[cityIndex] = result;

                                // تحديث الـ marker
                                if (cityMarkers[result.id]) {
                                    map.removeLayer(cityMarkers[result.id]);
                                }
                                addCityMarker(result);
                            }
                        }
                    } else {
                        // إضافة مدينة جديدة
                        result = await Livewire.find('{{ $this->getId() }}').call('createCity', data);

                        if (result) {
                            cities.push(result);
                            addCityMarker(result);

                            // تحديث العداد
                            document.getElementById('cities-count').textContent = cities.length;
                        }
                    }

                    closeCityModal();
                } catch (error) {
                    console.error('Error saving city:', error);
                    alert('حدث خطأ أثناء الحفظ');
                }
            }

            // تحديث موقع المدينة
            async function updateCityLocation(cityId, lat, lng) {
                try {
                    await Livewire.find('{{ $this->getId() }}').call('updateCityLocation', cityId, lat, lng);

                    // تحديث البيانات محلياً
                    const city = cities.find(c => c.id === cityId);
                    if (city) {
                        city.lat = lat;
                        city.lng = lng;
                    }
                } catch (error) {
                    console.error('Error updating location:', error);
                }
            }

            // تأكيد حذف المدينة
            function confirmDeleteCity(cityId) {
                const city = cities.find(c => c.id === cityId);
                const cityName = city ? city.name.ar : 'المدينة';

                if (confirm(`هل أنت متأكد من حذف "${cityName}"؟\n\nسيتم حذف جميع البيانات المرتبطة بها.`)) {
                    deleteCity(cityId);
                }
            }

            // حذف المدينة
            async function deleteCity(cityId) {
                try {
                    const result = await Livewire.find('{{ $this->getId() }}').call('deleteCity', cityId);

                    if (result) {
                        // حذف من القائمة
                        cities = cities.filter(c => c.id !== cityId);

                        // حذف الـ marker
                        if (cityMarkers[cityId]) {
                            map.removeLayer(cityMarkers[cityId]);
                            delete cityMarkers[cityId];
                        }

                        // تحديث العداد
                        document.getElementById('cities-count').textContent = cities.length;
                    }
                } catch (error) {
                    console.error('Error deleting city:', error);
                }
            }

            // إغلاق Modal بالضغط على Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeCityModal();
                }
            });

            // تهيئة الخريطة عند تحميل الصفحة
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded, initializing map...');
                initMap();
            });

            // ✅ إعادة تهيئة الخريطة بعد Livewire updates
            Livewire.hook('morph.updated', ({ el, component }) => {
                const mapElement = document.getElementById('cities-map');
                if (mapElement && !mapElement._leaflet_id) {
                    console.log('Re-initializing cities map after Livewire update...');
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

            /* تخصيص Tooltip */
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

            /* تخصيص Popup */
            .custom-popup .leaflet-popup-content-wrapper {
                border-radius: 10px !important;
                padding: 8px !important;
            }

            .custom-popup .leaflet-popup-content {
                margin: 8px !important;
            }

            /* تحسين المظهر في Dark Mode */
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
