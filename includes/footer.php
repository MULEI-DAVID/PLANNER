                    </div> <!-- .main-content -->
                </div> <!-- .col-lg-10 -->
            </div> <!-- .row -->
        </div> <!-- .container-fluid -->
    </div> <!-- .dashboard-container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/app.js"></script>
    
    <!-- Mobile Navigation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileSidebar = document.getElementById('mobileSidebar');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            console.log('Mobile sidebar elements:', {
                toggle: sidebarToggle,
                sidebar: mobileSidebar,
                overlay: sidebarOverlay
            });
            
            // Mobile sidebar toggle
            if (sidebarToggle && mobileSidebar) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Toggle button clicked');
                    
                    const isVisible = mobileSidebar.classList.contains('show');
                    console.log('Sidebar currently visible:', isVisible);
                    
                    if (isVisible) {
                        mobileSidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                        document.body.style.overflow = '';
                        console.log('Sidebar hidden');
                    } else {
                        mobileSidebar.classList.add('show');
                        sidebarOverlay.classList.add('show');
                        document.body.style.overflow = 'hidden';
                        console.log('Sidebar shown');
                    }
                });
            } else {
                console.error('Mobile sidebar elements not found:', {
                    toggle: !!sidebarToggle,
                    sidebar: !!mobileSidebar
                });
            }
            
            // Close sidebar when clicking overlay
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    console.log('Overlay clicked - closing sidebar');
                    mobileSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }
            
            // Close sidebar when clicking on a link (mobile)
            const mobileSidebarLinks = document.querySelectorAll('#mobileSidebar .sidebar-menu a');
            mobileSidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    console.log('Sidebar link clicked - closing sidebar');
                    mobileSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    console.log('Window resized - closing mobile sidebar');
                    mobileSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
            
            // Close sidebar when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileSidebar.classList.contains('show')) {
                    console.log('Escape key pressed - closing sidebar');
                    mobileSidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
            
            // Touch-friendly improvements
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
                console.log('Touch device detected');
            }
        });
    </script>
</body>
</html>

