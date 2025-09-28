    </div> <!-- End container-fluid -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- DataTables Export Extensions -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo (basename(dirname($_SERVER['PHP_SELF'])) == 'view') ? '../' : ''; ?>assets/js/app.js"></script>
    
    <script>
        // DataTables are now initialized individually on each page with server-side processing
        
        // Load notifications only if container exists and user is logged in
        const notificationsContainer = document.getElementById('notifications-content');
        if (notificationsContainer) {
            // Disable notifications for now to prevent errors
            notificationsContainer.innerHTML = '<div class="text-muted">Notifications disabled</div>';
            
            // Uncomment below to re-enable notifications when database is stable
            /*
            loadNotifications();
            
            function loadNotifications() {
                fetch('api/notifications.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.length > 0) {
                            notificationsContainer.innerHTML = data.map(notification => 
                                `<div class="mb-2">
                                    <strong>${notification.title}:</strong> ${notification.message}
                                    <small class="text-muted">(${notification.time})</small>
                                </div>`
                            ).join('');
                        } else {
                            notificationsContainer.innerHTML = '<div class="text-muted">No new notifications</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading notifications:', error);
                        notificationsContainer.innerHTML = '<div class="text-muted">Notifications temporarily unavailable</div>';
                    });
            }
            
            // Auto-refresh notifications every 30 seconds
            setInterval(loadNotifications, 30000);
            */
        }
    </script>
</body>
</html>
