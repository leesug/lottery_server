<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">로또 관리 시스템</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="../index.php">대시보드</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $currentSection === 'finance' ? 'active' : ''; ?>" href="#" id="financeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        재무 관리
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="financeDropdown">
                        <li><a class="dropdown-item" href="transactions.php">거래 관리</a></li>
                        <li><a class="dropdown-item" href="settlements.php">정산 관리</a></li>
                        <li><a class="dropdown-item" href="funds.php">기금 관리</a></li>
                        <li><a class="dropdown-item" href="budget-periods.php">예산 관리</a></li>
                        <li><a class="dropdown-item" href="reports.php">보고서</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
