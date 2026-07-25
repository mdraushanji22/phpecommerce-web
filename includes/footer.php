    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="bi bi-shop"></i> <?php echo SITE_NAME; ?></h5>
                    <p>Your one-stop shop for all your needs. Quality products at the best prices.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php" class="text-white text-decoration-none">Products</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/cart.php" class="text-white text-decoration-none">Cart</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact Us</h5>
                    <p>
                        <i class="bi bi-envelope"></i> mdraushanji22@gmail.com<br>
                        <i class="bi bi-telephone"></i> +91 6280779503<br>
                        <i class="bi bi-geo-alt"></i> Delhi, India
                    </p>
                </div>
            </div>
            <hr class="bg-white">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

<?php if (!empty($_SESSION['show_location_prompt'])): ?>
<?php unset($_SESSION['show_location_prompt']); ?>
    <!-- Location Permission Modal -->
    <div class="modal fade" id="locationPermissionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-geo-alt"></i> Location Access</h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-geo-alt-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Would you like to share your location?</h5>
                    <p class="text-muted mb-0">Allowing location access helps us autofill your shipping address for faster checkout.</p>
                    <div id="locationStatus" class="mt-3" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        <span>Fetching your location...</span>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" id="locationSkipBtn">Skip</button>
                    <button type="button" class="btn btn-primary" id="locationAllowBtn">
                        <i class="bi bi-check-lg"></i> Allow Location
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var modal = new bootstrap.Modal(document.getElementById('locationPermissionModal'));
        modal.show();

        document.getElementById('locationSkipBtn').addEventListener('click', function() {
            modal.hide();
        });

        document.getElementById('locationAllowBtn').addEventListener('click', function() {
            var statusEl = document.getElementById('locationStatus');
            statusEl.style.display = 'block';
            document.getElementById('locationAllowBtn').disabled = true;
            document.getElementById('locationSkipBtn').disabled = true;

            if (!navigator.geolocation) {
                statusEl.innerHTML = '<span class="text-danger">Geolocation is not supported by your browser.</span>';
                setTimeout(function() { modal.hide(); }, 2000);
                return;
            }

            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                statusEl.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div><span>Fetching address...</span>';

                fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lon + '&zoom=18&addressdetails=1', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    var addr = data.address || {};
                    var locationData = {
                        lat: lat,
                        lon: lon,
                        address: [addr.house_number, addr.road, addr.neighbourhood, addr.suburb].filter(Boolean).join(', ') || data.display_name || '',
                        city: addr.city || addr.town || addr.village || addr.county || '',
                        state: addr.state || '',
                        pincode: addr.postcode || ''
                    };
                    localStorage.setItem('userLocation', JSON.stringify(locationData));
                    statusEl.innerHTML = '<i class="text-success me-1"></i><span>Location saved! It will be used at checkout.</span>';
                    setTimeout(function() { modal.hide(); }, 1500);
                })
                .catch(function() {
                    statusEl.innerHTML = '<span class="text-danger">Could not fetch address. You can set it manually at checkout.</span>';
                    setTimeout(function() { modal.hide(); }, 2000);
                });
            }, function(error) {
                var msg = 'Location access was denied.';
                if (error.code === error.TIMEOUT) msg = 'Location request timed out.';
                statusEl.innerHTML = '<span class="text-danger">' + msg + '</span>';
                setTimeout(function() { modal.hide(); }, 2000);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 });
        });
    })();
    </script>
<?php endif; ?>
    </body>

    </html>
