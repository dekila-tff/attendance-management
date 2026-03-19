<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Attendance')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-[#07292d] to-[#0f3b40] text-white antialiased">
    @yield('content')

    <script>
        function captureLocation(event, formId, locationFieldId, buttonId) {
            if (navigator.geolocation) {
                event.preventDefault();
                const submitButton = document.getElementById(buttonId);
                const originalText = submitButton.textContent;
                submitButton.textContent = 'Getting location...';
                submitButton.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                            const data = await response.json();
                            const address = data.display_name || `Lat: ${lat}, Lng: ${lng}`;
                            document.getElementById(locationFieldId).value = address;
                        } catch (error) {
                            document.getElementById(locationFieldId).value = `Lat: ${lat}, Lng: ${lng}`;
                        }
                        
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        document.getElementById(formId).submit();
                    },
                    function(error) {
                        console.error('Geolocation error:', error);
                        document.getElementById(locationFieldId).value = 'Location permission denied';
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                        document.getElementById(formId).submit();
                    }
                );
                return false;
            }
            return true;
        }
    </script>
    @stack('scripts')
</body>
</html>
