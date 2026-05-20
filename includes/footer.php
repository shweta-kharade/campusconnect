    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/campusconnect/assets/js/script.js"></script>
    
    <script>
    $(document).ready(function() {
        // Global AJAX setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
    
    function showToast(message, type = 'success') {
        let bgColor = type === 'success' ? '#057642' : '#dc3545';
        let toastHtml = `
            <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; 
                        background: ${bgColor}; color: white; padding: 12px 24px; 
                        border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                ${message}
            </div>
        `;
        $('body').append(toastHtml);
        setTimeout(function() {
            $('body').find('div:last').fadeOut(function() { $(this).remove(); });
        }, 3000);
    }
    </script>
</body>
</html>