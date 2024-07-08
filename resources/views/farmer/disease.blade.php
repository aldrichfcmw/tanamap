@push('head')
{{-- <link rel="stylesheet" href="{{asset('/vendor/libs/datatables-bs5/datatables.bootstrap5')}}">
<link rel="stylesheet" href="{{asset('/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5')}}"> --}}
@endpush
@extends('layouts.farmer')
@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="row h-md-100">
        <div class="col-md-6 col-lg-5 mb-3">
            <div class="card mb-3">
                <h5 class="card-header">Table Disease</h5>
                <div class="card-body row">
                    <div class="card-datatable table-responsive">
                        <table class="dt-responsive table nowrap" id="tabel-data">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @foreach ($data as $key => $d)
                                <tr class="location-row" data-name="{{ $d -> disease_name }}" data-lat="{{ $d->latitude }}" data-long="{{ $d->longitude }}" data-status="{{ $d->health_status }}" data-img="{{ asset('storage/images/disease/' . $d->image) }}">
                                    <td>{{ $d -> disease_name }}</td>
                                    <td>{{ $d->health_status == '0' ? 'Sehat' : '' }}
                                        {{ $d->health_status == '1' ? 'Terindikasi Hama/Penyakit' : '' }}
                                    </td>  
                                    <td class="text-center">
                                        <a class="text-dark" href="https://www.google.com/maps?q={{ $d -> latitude }},{{ $d -> longitude }}"><i class="bx bx-link-external me-1"></i></a>                  
                                    </td>
                                </tr>
                                @endforeach  
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card" id="image-card" style="">
                <h5 class="card-header">Image:  <span id="image-name"></span></h5>
                <div class="card-body">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                          <img id="card-image" class="img-fluid rounded my-4" src="" height="500" width="500" alt="&nbsp Select row table to show image">
                        </div>
                      </div>
                    {{-- <img id="card-image" src="" alt="&nbsp Select row table to show image" style="height: 500px;"> --}}
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-7 mb-3">
            <div class="card h-md-100">
              {{-- <h5 class="card-header">Layer Control</h5> --}}
              <div class="card-body">
                {{-- <div class="leaflet-map h-md-100" id="layerControl"></div> --}}
                <div class="leaflet-map h-md-100" id="map"></div>
              </div>
            </div>
          </div>
    </div>
</div>  
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    var averageLat = {{ $avgLat }};
    var averageLong = {{ $avgLong }};
    var map = L.map('map').setView([averageLat, averageLong], 8);

    // Layer jalan dari Google Maps
    var roadmapLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxZoom: 19,
    });

    // Layer satelit dari Google Maps
    var satelliteLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        maxZoom: 19,
    });

    // Tambahkan layer jalan ke peta sebagai default
    roadmapLayer.addTo(map);

    // Tambahkan kontrol layer untuk beralih antara layer jalan dan satelit
    var baseMaps = {
        "Roadmap": roadmapLayer,
        "Satellite": satelliteLayer
    };

    L.control.layers(baseMaps).addTo(map);

    var locations = @json($data); // Pass PHP variable to JavaScript

    var markers = [];
    var activeMarker = null;

    function getCustomIcon(status, size = [30, 47]) {
        var iconUrl;
        if (status == 0) {
            iconUrl = '{{ asset("img/icons/marker/green-marker.png") }}';
        } else if (status == 1) {
            iconUrl = '{{ asset("img/icons/marker/red-marker.png") }}';
        }

        return L.icon({
            iconUrl: iconUrl,
            iconSize: size, // Ukuran gambar marker
            iconAnchor: [size[0] / 2, size[1]], // Posisi anchor marker
            popupAnchor: [0, -size[1]] // Posisi popup di atas marker
        });
    }

    locations.forEach(function(location) {
        var marker = L.marker([location.latitude, location.longitude], {icon: getCustomIcon(location.health_status), status: location.health_status}).addTo(map)
            .bindPopup('<b>' + location.disease_name + '</b><br>Latitude: ' + location.latitude + '<br>Longitude: ' + location.longitude);
        
        markers.push({marker: marker, status: location.status});

        marker.on('click', function() {
            if (activeMarker) {
                activeMarker.setIcon(getCustomIcon(activeMarker.options.status));
            }
            this.setIcon(getCustomIcon(location.health_status, [40, 63])); // Set to larger size when clicked
            activeMarker = this;
        });    
    });

    document.querySelectorAll('.location-row').forEach(function(row) {
        row.addEventListener('click', function() {
            var lat = parseFloat(this.dataset.lat);
            var long = parseFloat(this.dataset.long);
            var status = this.dataset.status;

            map.setView([lat, long], 22); // Pan map to the clicked location

            var selectedMarker = markers.find(function(item) {
                return item.marker.getLatLng().lat === lat && item.marker.getLatLng().lng === long;
            });

            if (selectedMarker) {
                if (activeMarker) {
                    activeMarker.setIcon(getCustomIcon(activeMarker.options.status));
                }
                selectedMarker.marker.setIcon(getCustomIcon(status, [40, 63])); // Set to larger size when clicked
                activeMarker = selectedMarker.marker;
            }
            var imageSrc  = row.getAttribute('data-img');
            var rowSrc    = row.getAttribute('data-name');
            var landArea  = row.getAttribute('data-area')
            var imageCard = document.getElementById('image-card');
            var cardImage = document.getElementById('card-image');

            cardImage.src = imageSrc;
            imageCard.style.display = 'block';
            document.getElementById('image-name').innerHTML = rowSrc;
        });
    });
</script>
@endsection
