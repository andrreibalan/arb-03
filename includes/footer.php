        </div>
    </div> 

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Configurare DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/ro.json'
                },
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Toate"]],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                order: [[0, 'asc']]
            });
        });
        
        // Funcție pentru confirmarea ștergerii
        function confirmDelete(url, message = 'Sunteți sigur că doriți să ștergeți acest element?') {
            Swal.fire({
                title: 'Confirmare ștergere',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Da, șterge!',
                cancelButtonText: 'Anulează'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
            return false;
        }
        
        // Afișare mesaje de succes/eroare
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                title: 'Succes!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                icon: 'success',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                title: 'Eroare!',
                text: '<?php echo $_SESSION['error_message']; ?>',
                icon: 'error',
                timer: 5000,
                showConfirmButton: true
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        // Validare formulare
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>



<script>
    // ... (restul codului tău DataTables, etc.)

    // Funcție pentru actualizarea ceasului în timp real
    function updateClock() {
        const now = new Date(); // Preluarea datei și orei curente
        
        // Formatează ziua (01, 02, ..., 31)
        const day = String(now.getDate()).padStart(2, '0');
        // Formatează luna (01, 02, ..., 12). +1 pentru că lunile încep de la 0
        const month = String(now.getMonth() + 1).padStart(2, '0'); 
        // Anul (YYYY)
        const year = now.getFullYear(); 
        
        // Ora (HH)
        const hours = String(now.getHours()).padStart(2, '0');
        // Minutele (MM)
        const minutes = String(now.getMinutes()).padStart(2, '0');
        // Secundele (SS) - opțional, dar util pentru a vedea actualizarea
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        // Crearea formatului dorit: DD.MM.YYYY HH:MM:SS
        const timeString = `${day}.${month}.${year} ${hours}:${minutes}:${seconds}`;

        // Afișează ora în elementul cu ID-ul "live-clock"
        document.getElementById('live-clock').textContent = timeString;
    }

    // 1. Rulează funcția imediat la încărcarea paginii
    updateClock();

    // 2. Rulează funcția la fiecare secundă (1000 milisecunde)
    setInterval(updateClock, 1000); 

</script>




</body>
</html>