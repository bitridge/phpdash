    </div> <!-- End container -->
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Current Date/Time Update Script -->
    <script>
    function updateDateTime() {
        const element = document.getElementById('current-datetime');
        if (!element) return;

        fetch('ajax/get_current_time.php')
            .then(response => response.text())
            .then(datetime => {
                element.textContent = datetime;
            })
            .catch(error => console.error('Error updating datetime:', error));
    }

    // Update every minute
    setInterval(updateDateTime, 60000);
    </script>

    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html> 