        </main>

        <!-- Footer -->
        <footer style="text-align: center; padding: 1rem; color: var(--color-gray-500); font-size: 0.875rem;">
            <p>&copy; <?= date('Y') ?> MJCRSoftware - FrigoTIC v<?= htmlspecialchars(getAppVersion()) ?></p>
        </footer>
    </div>

    <!-- Modal de Ayuda -->
    <div class="modal-overlay" id="helpModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-question-circle"></i> Ayuda
                </h2>
                <button class="modal-close" onclick="closeHelpModal()">&times;</button>
            </div>
            <div class="modal-body help-content" id="helpContent">
                Cargando...
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeHelpModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="/js/app.js"></script>
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
