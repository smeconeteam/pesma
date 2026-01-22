<script>
    // Hapus state grup dari localStorage saat load
    (function() {
        // Hapus semua key yang berkaitan dengan sidebar group state
        Object.keys(localStorage).forEach(function(key) {
            if (key.includes('sidebarGroup') || key.includes('sidebar-group') || key.includes('filament')) {
                localStorage.removeItem(key);
            }
        });
        
        // Hapus sessionStorage juga
        Object.keys(sessionStorage).forEach(function(key) {
            if (key.includes('sidebarGroup') || key.includes('sidebar-group') || key.includes('filament')) {
                sessionStorage.removeItem(key);
            }
        });
    })();
</script>