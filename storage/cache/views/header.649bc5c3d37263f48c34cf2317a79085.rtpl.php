<?php if(!class_exists('Rain\Tpl')){exit;}?><!doctype html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>PRATO CHEIO</title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="title" content="PRATO CHEIO | Painel Administrativo" />
  <meta name="author" content="ColorlibHQ" />
  <meta name="description" content="Sistema administrativo PRATO CHEIO" />
  <meta name="csrf-token" content="<?php echo htmlspecialchars( $csrf_token, ENT_COMPAT, 'UTF-8', FALSE ); ?>" />

  <script>
    (function () {
      const tokenElement = document.querySelector('meta[name="csrf-token"]');
      const token = tokenElement ? tokenElement.getAttribute('content') : '';
      const originalFetch = window.fetch;

      window.fetch = function (input, init) {
        init = init || {};
        const method = String(init.method || 'GET').toUpperCase();

        if (token && !['GET', 'HEAD', 'OPTIONS'].includes(method)) {
          const headers = new Headers(init.headers || {});
          if (!headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', token);
          }
          init.headers = headers;
          if (!init.credentials) init.credentials = 'same-origin';
        }

        return originalFetch(input, init);
      };

      document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!token || !form || !form.matches || !form.matches('form')) return;
        const method = String(form.getAttribute('method') || 'GET').toUpperCase();
        if (['GET', 'HEAD', 'OPTIONS'].includes(method)) return;
        if (form.querySelector('input[name="csrf_token"]')) return;

        const field = document.createElement('input');
        field.type = 'hidden';
        field.name = 'csrf_token';
        field.value = token;
        form.appendChild(field);
      }, true);

      function configurarJqueryCsrf() {
        if (!token || !window.jQuery || !window.jQuery.ajaxSetup) return;

        window.jQuery.ajaxSetup({
          beforeSend: function (xhr, settings) {
            const method = String(settings && settings.type ? settings.type : 'GET').toUpperCase();
            if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
              xhr.setRequestHeader('X-CSRF-Token', token);
              xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            }
          }
        });
      }

      configurarJqueryCsrf();
      document.addEventListener('DOMContentLoaded', configurarJqueryCsrf);
    })();
  </script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="/res/admin/dist/css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" />

  <style>
    /* GARANTE QUE O TOPO FIQUE SEMPRE ACIMA */
    .main-header {
      position: sticky;
      top: 0;
      z-index: 9999 !important;
    }

    /* DROPDOWN DO USUÁRIO / LOGOUT */
    .navbar-nav .dropdown-menu {
      z-index: 10000 !important;
    }

    /* CORREÇÃO GLOBAL DE BACKDROP TRAVADO */
    .modal-backdrop {
      z-index: 1040 !important;
    }

    /* GARANTE QUE MODAL NÃO FIQUE ACIMA DO HEADER */
    .modal {
      z-index: 1050 !important;
    }
  </style>

</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">

    <!-- HEADER -->
    <nav class="app-header navbar navbar-expand bg-body painel-header">
      <div class="container-fluid">

        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link btn-menu" data-lte-toggle="sidebar" href="#" role="button">
              <i class="bi bi-list"></i>
            </a>
          </li>
          <li class="nav-item d-none d-md-block">
            <a href="/admin" class="nav-link nav-home-link">Home</a>
          </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center">

          <!-- NOTIFICAÇÕES -->
          <li class="nav-item dropdown">
            <a class="nav-link nav-icone-topo position-relative" data-bs-toggle="dropdown" href="#">
              <i class="bi bi-bell-fill"></i>
              <span id="badge-notificacoes" class="navbar-badge badge text-bg-warning"
                style="<?php if( $total <= 0 ){ ?>display:none;<?php } ?>">
                <?php echo htmlspecialchars( $total, ENT_COMPAT, 'UTF-8', FALSE ); ?>

              </span>
            </a>

            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end dropdown-notificacoes shadow-sm"
              id="dropdownNotificacoes">
              <div class="dropdown-header-notificacao">
                <div>
                  <strong>Notificações</strong>
                  <div class="small text-muted" id="contadorNotificacoesTexto">
                    <?php echo htmlspecialchars( $total, ENT_COMPAT, 'UTF-8', FALSE ); ?> item(ns)
                  </div>
                </div>

                <button type="button" id="btn-limpar-notificacoes"
                  class="btn btn-sm btn-outline-danger btn-limpar-notificacoes">
                  <i class="bi bi-trash3"></i> Limpar
                </button>
              </div>

              <div class="dropdown-divider"></div>

              <?php if( $total == 0 ){ ?>

              <div class="dropdown-item text-center text-muted py-3" id="estadoVazioNotificacoes">
                <i class="bi bi-bell-slash d-block fs-4 mb-2"></i>
                Nenhuma notificação.
              </div>
              <?php }else{ ?>

              <div class="lista-notificacoes" id="listaNotificacoes">
                <?php $counter1=-1;  if( isset($notificacoes) && ( is_array($notificacoes) || $notificacoes instanceof Traversable ) && sizeof($notificacoes) ) foreach( $notificacoes as $key1 => $value1 ){ $counter1++; ?>

                <a href="#" class="dropdown-item item-notificacao">
                  <div class="d-flex align-items-start">
                    <div class="icone-notificacao">
                      <i class="bi bi-cloud-check-fill text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="texto-notificacao"><?php echo htmlspecialchars( $value1["msg"], ENT_COMPAT, 'UTF-8', FALSE ); ?></div>
                      <small class="text-muted"><?php echo htmlspecialchars( $value1["time"], ENT_COMPAT, 'UTF-8', FALSE ); ?></small>
                    </div>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
                <?php } ?>

              </div>
              <?php } ?>


              <a href="/admin/notificacoes" class="dropdown-item dropdown-footer">
                Ver notificações
              </a>
            </div>
          </li>

          <!-- FULLSCREEN -->
          <li class="nav-item">
            <a class="nav-link nav-icone-topo" href="#" data-lte-toggle="fullscreen">
              <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
              <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
            </a>
          </li>

          <!-- USUÁRIO -->
          <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle user-topo" data-bs-toggle="dropdown">
              <img src="/res/admin/dist/assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow-sm"
                alt="User Image" />
              <span class="d-none d-md-inline"><?php echo getUserName(); ?></span>
            </a>

            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow-sm">
              <li class="user-header text-bg-primary perfil-header">
                <img src="/res/admin/dist/assets/img/user2-160x160.jpg" class="rounded-circle shadow"
                  alt="User Image" />
                <p>
                  <?php echo getUserName(); ?>

                  <small>Membro desde Nov. 2023</small>
                </p>
              </li>

              <li class="user-footer">
                <a href="#" class="btn btn-default btn-flat">Perfil</a>
                <a href="/admin/logout" class="btn btn-danger btn-flat float-end">Sair</a>
              </li>
            </ul>
          </li>

        </ul>
      </div>
    </nav>

    <!-- SIDEBAR -->
    <aside class="app-sidebar bg-body-secondary sidebar-custom" data-bs-theme="light">
      <div class="sidebar-brand">
        <a href="/admin" class="brand-link brand-custom">
          <img src="/res/admin/dist/assets/img/AdminLTELogo.png" alt="Logo" class="brand-image opacity-75 shadow-sm" />
          <span class="brand-text fw-semibold">PRATO CHEIO</span>
        </a>
      </div>

      <div class="sidebar-wrapper">
        <nav class="mt-2">
          <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

            <li class="nav-header titulo-menu">PRINCIPAL</li>
            <?php if( canAccess('DASHBOARD_VIEW') ){ ?>

            <li class="nav-item">
              <a href="/admin" class="nav-link">
                <i class="nav-icon bi bi-speedometer2"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <?php } ?>



            <li class="nav-header titulo-menu">CADASTROS</li>
            <li class="nav-item menu-open">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-folder2-open"></i>
                <p>
                  Cadastros
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <?php if( canAccess('FUNCIONARIOS_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/funcionarios" class="nav-link active">
                    <i class="nav-icon bi bi-person-badge"></i>
                    <p>Funcionários</p>
                  </a>
                </li>
                <?php } ?>


                <?php if( canAccess('CLIENTES_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/clientes" class="nav-link">
                    <i class="nav-icon bi bi-people"></i>
                    <p>Clientes</p>
                  </a>
                </li>
                <?php } ?>


                <?php if( canAccess('SOCIO_ECONOMICO_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/socio-economico" class="nav-link">
                    <i class="nav-icon bi bi-clipboard-data"></i>
                    <p>Socio Economico</p>
                  </a>
                </li>
                <?php } ?>


                <?php if( canAccess('SOCIO_ECONOMICO_SAUDE_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/socio-economico-saude" class="nav-link">
                    <i class="nav-icon bi bi-heart-pulse"></i>
                    <p>Socio Economico Saude</p>
                  </a>
                </li>
                <?php } ?>

              </ul>
            </li>

            <li class="nav-header titulo-menu">MOVIMENTOS</li>
            <li class="nav-item menu-open">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-cart-check"></i>
                <p>
                  Vendas
                  <i class="nav-arrow bi bi-chevron-right"></i>
                </p>
              </a>
              <ul class="nav nav-treeview">
                <?php if( canAccess('VENDAS_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/vendas" class="nav-link">
                    <i class="nav-icon bi bi-ticket-perforated"></i>
                    <p>Senhas</p>
                  </a>
                </li>
                <?php } ?>


                <?php if( canAccess('RELATORIOS_VIEW') ){ ?>

                <li class="nav-item">
                  <a href="/admin/relatorio/senhas" class="nav-link">
                    <i class="nav-icon bi bi-file-earmark-text"></i>
                    <p>Expediente</p>
                  </a>
                </li>
                <?php } ?>

              </ul>
            </li>

            <?php if( canAnyAccess(['ACL_PROFILES_MANAGE','ACL_DENIED_VIEW','AUDITORIA_VIEW','USUARIOS_SECURITY_MANAGE']) || canManageSystemSettings() ){ ?>

            <li class="nav-header titulo-menu">SEGURANÇA</li>

            <?php if( canAccess('ACL_PROFILES_MANAGE') ){ ?>

            <li class="nav-item">
              <a href="/admin/seguranca/permissoes" class="nav-link">
                <i class="nav-icon bi bi-shield-lock"></i>
                <p>Permissões por Perfil</p>
              </a>
            </li>
            <?php } ?>


            <?php if( canAccess('ACL_DENIED_VIEW') ){ ?>

            <li class="nav-item">
              <a href="/admin/seguranca/acessos-negados" class="nav-link">
                <i class="nav-icon bi bi-ban"></i>
                <p>Acessos Negados</p>
              </a>
            </li>
            <?php } ?>


            <?php if( canAccess('AUDITORIA_VIEW') ){ ?>

            <li class="nav-item">
              <a href="/admin/seguranca/auditoria" class="nav-link">
                <i class="nav-icon bi bi-clipboard-data"></i>
                <p>Auditoria</p>
              </a>
            </li>
            <?php } ?>


            <?php if( canAccess('USUARIOS_SECURITY_MANAGE') ){ ?>

            <li class="nav-item">
              <a href="/admin/usuarios/seguranca" class="nav-link">
                <i class="nav-icon bi bi-person-lock"></i>
                <p>Segurança dos Usuários</p>
              </a>
            </li>
            <?php } ?>


            <?php if( canManageSystemSettings() ){ ?>

            <li class="nav-item">
              <a href="/admin/configuracoes/sistema" class="nav-link">
                <i class="nav-icon bi bi-sliders"></i>
                <p>Configurações do Sistema</p>
              </a>
            </li>
            <?php } ?>

            <?php } ?>


          </ul>
        </nav>
      </div>
    </aside>

    <style>
      :root {
        --bg-page: #f5f7fb;
        --bg-surface: #ffffff;
        --bg-surface-soft: #f8fafc;
        --border-soft: #e2e8f0;
        --border-mid: #d9e1e7;

        --text-dark: #020617;
        --text-main: #0f172a;
        --text-soft: #334155;
        --text-muted: #64748b;

        --primary: #2563eb;
        --primary-soft: #dbeafe;
        --primary-soft-2: #eff6ff;
        --primary-hover: #1f6fb2;

        --success: #22c55e;
        --warning: #f59e0b;
        --danger: #ef4444;

        --shadow-xs: 0 1px 4px rgba(0, 0, 0, 0.04);
        --shadow-sm: 0 4px 16px rgba(15, 23, 42, 0.05);
        --shadow-md: 0 10px 28px rgba(15, 23, 42, 0.06);
        --shadow-lg: 0 12px 30px rgba(0, 0, 0, 0.12);

        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 12px;
        --radius-xl: 16px;

        --transition: all 0.22s ease;
      }

      * {
        box-sizing: border-box;
      }

      html,
      body {
        font-family: "Source Sans 3", sans-serif;
        background: var(--bg-page) !important;
        background-image: none !important;
        color: var(--text-main) !important;
        min-height: 100vh;
      }

      body.layout-fixed .app-wrapper,
      .app-main,
      .app-content,
      .app-content-header,
      .content-wrapper,
      .wrapper {
        background: var(--bg-page) !important;
      }

      /* =========================
     HEADER
  ========================= */
      .painel-header {
        background: rgba(255, 255, 255, 0.95) !important;
        border-bottom: 1px solid var(--border-soft) !important;
        box-shadow: var(--shadow-sm) !important;
      }

      .btn-menu,
      .nav-home-link,
      .nav-icone-topo,
      .user-topo,
      .user-topo span {
        color: var(--text-main) !important;
        font-weight: 700 !important;
      }

      .btn-menu,
      .nav-icone-topo {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
        transition: var(--transition);
      }

      .btn-menu:hover,
      .nav-icone-topo:hover {
        background: #f1f5f9 !important;
        color: var(--primary) !important;
      }

      .nav-home-link {
        font-weight: 700;
        border-radius: var(--radius-lg);
        padding: 9px 14px !important;
        transition: var(--transition);
      }

      .nav-home-link:hover {
        background: #eef6ff !important;
        color: var(--primary) !important;
      }

      .navbar-badge {
        font-size: 11px;
        padding: 4px 6px;
        border-radius: 50px;
        box-shadow: 0 6px 14px rgba(245, 158, 11, 0.25);
      }

      /* =========================
     DROPDOWNS / NOTIFICAÇÕES
  ========================= */
      .dropdown-notificacoes,
      .user-menu .dropdown-menu {
        width: 360px;
        border: 1px solid var(--border-soft);
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        background: var(--bg-surface);
      }

      .dropdown-header-notificacao {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px 10px 16px;
        background: var(--bg-surface-soft);
        border-bottom: 1px solid var(--border-soft);
      }

      .dropdown-header-notificacao strong,
      .dropdown-footer,
      .texto-notificacao {
        color: var(--text-main) !important;
        font-weight: 700 !important;
      }

      .dropdown-header-notificacao .small,
      .texto-notificacao+small {
        color: var(--text-soft) !important;
        font-weight: 600 !important;
      }

      .btn-limpar-notificacoes {
        border-radius: var(--radius-sm);
        font-size: 12px;
      }

      .lista-notificacoes {
        max-height: 320px;
        overflow-y: auto;
      }

      .item-notificacao {
        padding: 12px 14px;
        transition: background 0.2s ease;
      }

      .item-notificacao:hover {
        background: #f4f8fb;
      }

      .icone-notificacao {
        width: 34px;
        min-width: 34px;
        text-align: center;
        margin-right: 10px;
        font-size: 18px;
      }

      .texto-notificacao {
        font-size: 14px;
        line-height: 1.3;
      }

      .dropdown-footer {
        text-align: center;
        background: #fafafa;
      }

      /* =========================
     USER MENU
  ========================= */
      .perfil-header {
        background: linear-gradient(135deg, #2e86c1, #3c8dbc) !important;
      }

      .user-footer {
        padding: 12px 14px;
      }

      .user-footer .btn {
        border-radius: var(--radius-sm);
      }

      /* =========================
     SIDEBAR
  ========================= */
      .sidebar-custom {
        background: linear-gradient(180deg, #f8fafc 0%, #f2f5f8 100%) !important;
        border-right: 1px solid var(--border-mid);
      }

      .brand-custom {
        background: var(--bg-surface);
        border-bottom: 1px solid #e6ebf0;
        padding-top: 12px;
        padding-bottom: 12px;
      }

      .brand-custom .brand-text {
        color: #243746;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0.2px;
      }

      .titulo-menu {
        font-size: 12px;
        font-weight: 800;
        color: #7c8b98 !important;
        letter-spacing: 0.9px;
        margin: 18px 14px 8px 14px;
        text-transform: uppercase;
      }

      /* =========================
     MENU LATERAL
  ========================= */
      .sidebar-menu .nav-item {
        margin-bottom: 2px;
      }

      .sidebar-menu .nav-link {
        position: relative;
        display: flex;
        align-items: center;
        border-radius: var(--radius-lg);
        margin: 4px 12px;
        padding: 11px 14px;
        color: #42515d !important;
        font-weight: 700 !important;
        transition: var(--transition);
        background: transparent;
      }

      .sidebar-menu .nav-link:hover {
        background: #eaf3fb;
        color: var(--primary-hover) !important;
        transform: translateX(2px);
      }

      .sidebar-menu .nav-link:hover .nav-icon,
      .sidebar-menu .nav-link:hover p,
      .sidebar-menu .nav-link:hover .nav-arrow {
        color: var(--primary-hover) !important;
      }

      .sidebar-menu .nav-link.active {
        background: linear-gradient(90deg, #dff0ff 0%, #edf7ff 100%) !important;
        color: #156fbf !important;
        font-weight: 800 !important;
        box-shadow: inset 4px 0 0 #1f6fb2, 0 2px 8px rgba(31, 111, 178, 0.08);
      }

      .sidebar-menu .nav-link.active .nav-icon,
      .sidebar-menu .nav-link.active p,
      .sidebar-menu .nav-link.active .nav-arrow {
        color: #156fbf !important;
      }

      .sidebar-menu .menu-open>.nav-link {
        background: #eef3f7;
        color: #2f4554 !important;
        font-weight: 700;
      }

      .sidebar-menu .menu-open>.nav-link .nav-icon,
      .sidebar-menu .menu-open>.nav-link .nav-arrow,
      .sidebar-menu .menu-open>.nav-link p {
        color: #2f4554 !important;
      }

      .sidebar-menu .nav-icon {
        margin-right: 10px;
        font-size: 18px;
        color: #7d8a97 !important;
        min-width: 22px;
        text-align: center;
        transition: var(--transition);
      }

      .sidebar-menu .nav-link p {
        margin: 0;
        color: inherit !important;
        font-size: 16px;
        line-height: 1.2;
        font-weight: 700 !important;
      }

      .sidebar-menu .nav-arrow {
        margin-left: auto;
        color: #7d8a97 !important;
        transition: transform 0.2s ease;
      }

      .sidebar-menu .menu-open>.nav-link .nav-arrow {
        transform: rotate(90deg);
      }

      /* =========================
     SUBMENU
  ========================= */
      .sidebar-menu .nav-treeview {
        position: relative;
        margin: 4px 0 10px 0;
        padding-left: 8px;
      }

      .sidebar-menu .nav-treeview::before {
        content: "";
        position: absolute;
        top: 2px;
        bottom: 8px;
        left: 26px;
        width: 2px;
        background: #d9e6f2;
        border-radius: 2px;
      }

      .sidebar-menu .nav-treeview .nav-item {
        position: relative;
      }

      .sidebar-menu .nav-treeview .nav-item::before {
        content: "";
        position: absolute;
        top: 19px;
        left: 26px;
        width: 16px;
        height: 2px;
        background: #d9e6f2;
        border-radius: 2px;
      }

      .sidebar-menu .nav-treeview .nav-link {
        margin-left: 36px;
        padding: 9px 14px;
        background: transparent;
        border-radius: var(--radius-md);
        font-size: 15px;
      }

      .sidebar-menu .nav-treeview .nav-link .nav-icon {
        font-size: 16px;
        color: #90a0ad !important;
      }

      .sidebar-menu .nav-treeview .nav-link:hover {
        background: #eef6fd;
      }

      .sidebar-menu .nav-treeview .nav-link.active {
        background: #e8f3ff !important;
        color: #156fbf !important;
        box-shadow: inset 3px 0 0 #1f6fb2;
      }

      .sidebar-menu .nav-treeview .nav-link.active .nav-icon,
      .sidebar-menu .nav-treeview .nav-link.active p {
        color: #156fbf !important;
      }

      /* =========================
     TIPOGRAFIA / LEGIBILIDADE
  ========================= */
      body,
      .app-main,
      .app-content,
      .app-content-header,
      .content-wrapper {
        color: var(--text-main) !important;
      }

      .page-title,
      h1,
      h2,
      h3,
      h4,
      h5,
      .card-title {
        color: var(--text-dark) !important;
        font-weight: 800 !important;
        letter-spacing: -0.2px;
      }

      .page-subtitle,
      .page-eyebrow,
      .card-subtitle,
      .card-text,
      p,
      .small,
      .text-muted {
        color: var(--text-soft) !important;
        font-weight: 500 !important;
      }

      /* =========================
     CARDS / PAINÉIS
  ========================= */
      .card,
      .chart-card,
      .backup-card,
      .saas-panel {
        background: var(--bg-surface) !important;
        border: 1px solid var(--border-soft) !important;
        box-shadow: var(--shadow-md) !important;
      }

      .saas-stat-card .stat-topline,
      .saas-stat-card .stat-label,
      .saas-stat-card .stat-meta {
        color: var(--text-soft) !important;
        font-weight: 700 !important;
      }

      .saas-stat-card .stat-number {
        color: var(--text-dark) !important;
        font-weight: 900 !important;
      }

      /* =========================
     TABELAS
  ========================= */
      .table,
      table {
        color: var(--text-main) !important;
      }

      .table thead th,
      table thead th {
        color: var(--text-soft) !important;
        font-weight: 800 !important;
        background: var(--bg-surface-soft) !important;
      }

      .table tbody td,
      table tbody td {
        color: var(--text-main) !important;
        font-weight: 600 !important;
      }

      .table tbody tr:hover,
      table tbody tr:hover {
        background: var(--bg-surface-soft) !important;
      }

      /* =========================
     GRÁFICOS
  ========================= */
      .apexcharts-text,
      .apexcharts-datalabel-label,
      .apexcharts-datalabel-value,
      .apexcharts-legend-text {
        fill: var(--text-main) !important;
        color: var(--text-main) !important;
        font-weight: 700 !important;
      }

      /* =========================
     RESPONSIVO
  ========================= */
      @media (max-width: 767px) {

        .dropdown-notificacoes,
        .user-menu .dropdown-menu {
          width: 300px;
        }
      }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("btn-limpar-notificacoes");
        if (!btn) return;

        btn.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();

          Swal.fire({
            title: "Limpar notificações?",
            text: "Todas as notificações serão removidas.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sim, limpar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#d33"
          }).then(function (result) {
            if (!result.isConfirmed) return;

            fetch("/admin/notificacoes/limpar", {
              method: "POST",
              headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json"
              }
            })
              .then(async function (r) {
                const texto = await r.text();

                try {
                  return JSON.parse(texto);
                } catch (e) {
                  throw new Error("A rota /admin/notificacoes/limpar não retornou JSON.");
                }
              })
              .then(function (data) {
                if (!data.success) {
                  throw new Error(data.message || "Falha ao limpar notificações.");
                }

                const badge = document.getElementById("badge-notificacoes");
                const lista = document.querySelector(".lista-notificacoes");
                const dropdown = document.getElementById("dropdownNotificacoes");
                const contadorTexto = dropdown ? dropdown.querySelector(".dropdown-header-notificacao .small") : null;

                if (badge) {
                  badge.textContent = "0";
                  badge.style.display = "none";
                }

                if (contadorTexto) {
                  contadorTexto.textContent = "0 item(ns)";
                }

                if (lista) {
                  lista.remove();
                }

                const vazioExistente = dropdown ? dropdown.querySelector(".estado-vazio-notificacoes") : null;

                if (dropdown && !vazioExistente) {
                  const footer = dropdown.querySelector(".dropdown-footer");
                  const empty = document.createElement("div");
                  empty.className = "dropdown-item text-center text-muted py-3 estado-vazio-notificacoes";
                  empty.innerHTML = '<i class="bi bi-bell-slash d-block fs-4 mb-2"></i>Nenhuma notificação.';

                  if (footer) {
                    footer.insertAdjacentElement("beforebegin", empty);
                  } else {
                    dropdown.appendChild(empty);
                  }
                }

                dropdown.querySelectorAll(".dropdown-divider").forEach(function (divisor) {
                  const prev = divisor.previousElementSibling;
                  const next = divisor.nextElementSibling;

                  if (
                    !prev ||
                    !next ||
                    prev.classList.contains("dropdown-header-notificacao") ||
                    prev.classList.contains("estado-vazio-notificacoes") ||
                    next.classList.contains("dropdown-footer")
                  ) {
                    divisor.remove();
                  }
                });

                Swal.fire({
                  toast: true,
                  position: "top-end",
                  icon: "success",
                  title: "Notificações limpas com sucesso",
                  showConfirmButton: false,
                  timer: 1800,
                  timerProgressBar: true
                });
              })
              .catch(function (err) {
                console.error(err);
                Swal.fire({
                  icon: "error",
                  title: "Erro",
                  text: err.message || "Não foi possível limpar as notificações."
                });
              });
          });
        });
      });

      let totalAnterior = Number("<?php echo htmlspecialchars( $total, ENT_COMPAT, 'UTF-8', FALSE ); ?>") || 0;
      let notificacoesInterval = null;

      function atualizarNotificacoes() {
        fetch("/admin/api/notificacoes", {
          headers: { "Accept": "application/json" }
        })
          .then(async res => {
            const texto = await res.text();
            let json = null;

            try {
              json = JSON.parse(texto);
            } catch (parseError) {
              throw new Error("A API retornou HTML em vez de JSON.");
            }

            if (!res.ok || json.success === false) {
              throw new Error(json.mensagem || `HTTP ${res.status}`);
            }

            return json;
          })
          .then(data => {
            const total = Number(data.total ?? 0);
            const badge = document.getElementById("badge-notificacoes");
            const dropdown = document.getElementById("dropdownNotificacoes");
            const contadorTexto = dropdown ? dropdown.querySelector(".dropdown-header-notificacao .small") : null;

            if (badge) {
              badge.innerText = total;
              badge.style.display = total > 0 ? "inline-block" : "none";
            }

            if (contadorTexto) {
              contadorTexto.textContent = `${total} item(ns)`;
            }

            if (total > totalAnterior) {
              Swal.fire({
                toast: true,
                position: "top-end",
                icon: "info",
                title: "Nova notificação recebida",
                showConfirmButton: false,
                timer: 3000
              });
            }

            totalAnterior = total;
          })
          .catch(err => {
            console.error("Erro detalhado:", err.message);

            if (notificacoesInterval) {
              clearInterval(notificacoesInterval);
              notificacoesInterval = null;
            }
          });
      }

      notificacoesInterval = setInterval(atualizarNotificacoes, 10000);
    </script>
