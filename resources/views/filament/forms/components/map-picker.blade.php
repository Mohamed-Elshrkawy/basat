<div>
    <div class="space-y-4">
        <!-- عرض الإحداثيات الحالية -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium text-blue-900 dark:text-blue-200">الموقع المحدد:</span>
                </div>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <span id="coordinates-display">تحميل...</span>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div id="form-map-container" class="form-map-container" style="height: 400px; width: 100%; border-radius: 8px; overflow: hidden; border: 2px solid #e5e7eb; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);"></div>

        <!-- تعليمات -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-md p-3">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p class="font-medium mb-1">كيفية تحديد الموقع:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>انقر على الخريطة لتحديد الموقع</li>
                        <li>اسحب العلامة لتعديل الموقع</li>
                        <li>استخدم عجلة الماوس للتكبير والتصغير</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <style>
                .leaflet-container {
                    z-index: 1 !important;
                }
                .form-map-container {
                    background: #f3f4f6;
                }
            </style>
        @endpush

        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(initializeFormMap, 500);
                });

                function initializeFormMap() {
                    const mapElement = document.getElementById('form-map-container');

                    if (!mapElement) {
                        setTimeout(initializeFormMap, 500);
                        return;
                    }

                    if (typeof L === 'undefined') {
                        setTimeout(initializeFormMap, 200);
                        return;
                    }

                    const latInput = document.querySelector('input[name="lat"]');
                    const lngInput = document.querySelector('input[name="lng"]');

                    let currentLat = parseFloat(latInput?.value) || 24.7136;
                    let currentLng = parseFloat(lngInput?.value) || 46.6753;

                    if (mapElement._leaflet_id) {
                        mapElement._leaflet_id = null;
                        mapElement.innerHTML = '';
                    }

                    const formMap = L.map(mapElement, {
                        center: [currentLat, currentLng],
                        zoom: 10,
                        scrollWheelZoom: true
                    });

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 18,
                    }).addTo(formMap);

                    let formMarker = null;

                    function addFormMarker(lat, lng) {
                        if (formMarker) {
                            formMap.removeLayer(formMarker);
                        }

                        const customIcon = L.divIcon({
                            html: `
                                <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                                    <path fill="#3b82f6" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                                    <circle cx="192" cy="192" r="60" fill="white"/>
                                </svg>
                            `,
                            className: '',
                            iconSize: [30, 49],
                            iconAnchor: [15, 49]
                        });

                        formMarker = L.marker([lat, lng], {
                            icon: customIcon,
                            draggable: true
                        }).addTo(formMap);

                        formMarker.on('dragend', function(e) {
                            const position = e.target.getLatLng();
                            updateFormInputs(position.lat, position.lng);
                        });

                        const popupContent = `
                            <div style="min-width: 200px; text-align: center;">
                                <strong style="font-size: 14px;">📍 الموقع المحدد</strong><br>
                                <span style="color: #6b7280; font-size: 12px;">خط العرض: ${lat.toFixed(6)}</span><br>
                                <span style="color: #6b7280; font-size: 12px;">خط الطول: ${lng.toFixed(6)}</span>
                            </div>
                        `;
                        formMarker.bindPopup(popupContent).openPopup();
                        formMap.setView([lat, lng], formMap.getZoom());
                    }

                    function updateFormInputs(lat, lng) {
                        if (latInput) {
                            latInput.value = lat.toFixed(7);
                            latInput.dispatchEvent(new Event('input', { bubbles: true }));
                            latInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        if (lngInput) {
                            lngInput.value = lng.toFixed(7);
                            lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                            lngInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }

                        const display = document.getElementById('coordinates-display');
                        if (display) {
                            display.textContent = `خط العرض: ${lat.toFixed(6)} | خط الطول: ${lng.toFixed(6)}`;
                        }
                    }

                    if (latInput?.value && lngInput?.value) {
                        addFormMarker(currentLat, currentLng);
                    }

                    updateFormInputs(currentLat, currentLng);

                    formMap.on('click', function(e) {
                        const { lat, lng } = e.latlng;
                        addFormMarker(lat, lng);
                        updateFormInputs(lat, lng);
                    });

                    setTimeout(function() {
                        formMap.invalidateSize();
                    }, 250);
                }
            </script>
        @endpush
    @endonce
</div>
