    </div>
    <!-- /.content-wrapper -->
    
    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0.0
        </div>
        <strong>Copyright &copy; 2025 <a href="#">KHUSHI LOTTERY</a>.</strong> All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.jsdelivr.net/npm/datatables.net@1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs4@1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.2.9/js/dataTables.responsive.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo SERVER_URL; ?>/assets/js/common.js"></script>

<?php if (isset($extraJs)): ?>
    <script src="<?php echo $extraJs; ?>"></script>
<?php endif; ?>

<?php if (isset($inlineJs)): ?>
    <script>
        <?php echo $inlineJs; ?>
    </script>
<?php endif; ?>

</body>
</html>