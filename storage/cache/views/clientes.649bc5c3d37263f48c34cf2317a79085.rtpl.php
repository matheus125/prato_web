<?php if(!class_exists('Rain\Tpl')){exit;}?><!-- CSS do DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- JS do DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="card mb-4 shadow-sm border-0 clientes-page-card">
  <div class="card-header clientes-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="card-title mb-0 fw-bold">CLIENTE</h3>
      <small class="text-muted">Consulta, visualização e gestão de titulares e dependentes</small>
    </div>
    <div class="clientes-page-actions d-flex gap-2">
      <a href="/admin/clientes/create" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i> Novo Titular
      </a>
      <a href="/admin/clientes/vulnerabilidade/create" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-person-heart me-1"></i> Novo Vulnerabilidade
      </a>
      <a href="/admin/dependente/create" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-people me-1"></i> Novo Dependente
      </a>
    </div>
  </div>

  <?php if( $msgSuccess != '' ){ ?>

  <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    <?php echo htmlspecialchars( $msgSuccess, ENT_COMPAT, 'UTF-8', FALSE ); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php } ?>


  <?php if( $msgError != '' ){ ?>

  <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <?php echo htmlspecialchars( $msgError, ENT_COMPAT, 'UTF-8', FALSE ); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php } ?>


  <div class="app-content">
    <div class="container-fluid">
      <div class="row">
        <div class="card mb-4 shadow-sm border-0">
          <div class="card-header bg-white border-0 pt-4 pb-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div>
                <h4 class="mb-1 fw-bold text-dark">Informações para contato</h4>
                <p class="text-muted mb-0">Lista de titulares cadastrados no sistema</p>
              </div>
            </div>
          </div>

          <div class="table-responsive p-3">
            <table id="tabela_titulares" class="table table-striped table-bordered align-middle w-100 clientes-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nome Completo</th>
                  <th>Telefone</th>
                  <th>Estado Civil</th>
                  <th>RG</th>
                  <th>CPF</th>
                  <th>SEXO</th>
                  <th>Status</th>
                  <th>NIS</th>
                  <th>Data Nascimento</th>
                  <th>Idade</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php $counter1=-1;  if( isset($lista_titulares) && ( is_array($lista_titulares) || $lista_titulares instanceof Traversable ) && sizeof($lista_titulares) ) foreach( $lista_titulares as $key1 => $value1 ){ $counter1++; ?>

                <tr>
                  <td><?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["nome_completo"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["telefone"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["estado_civil"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["rg"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["cpf"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["genero_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["status_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["nis"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["data_nascimento"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td><?php echo htmlspecialchars( $value1["idade_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?></td>
                  <td>
                    <div class="btn-group" role="group">
                      <button type="button" class="btn btn-sm btn-primary btn-detalhes" data-bs-toggle="modal"
                        data-bs-target="#modalRelatorio" title="Visualizar detalhes do titular" data-id="<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-nome="<?php echo htmlspecialchars( $value1["nome_completo"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-social="<?php echo htmlspecialchars( $value1["nome_social"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-mae="<?php echo htmlspecialchars( $value1["nome_mae"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-cor="<?php echo htmlspecialchars( $value1["cor_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-telefone="<?php echo htmlspecialchars( $value1["telefone"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-estado="<?php echo htmlspecialchars( $value1["estado_civil"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-rg="<?php echo htmlspecialchars( $value1["rg"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-cpf="<?php echo htmlspecialchars( $value1["cpf"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-sexo="<?php echo htmlspecialchars( $value1["genero_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-status="<?php echo htmlspecialchars( $value1["status_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-nis="<?php echo htmlspecialchars( $value1["nis"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-nascimento="<?php echo htmlspecialchars( $value1["data_nascimento"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-idade="<?php echo htmlspecialchars( $value1["idade_cliente"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-cep="<?php echo htmlspecialchars( $value1["cep"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-bairro="<?php echo htmlspecialchars( $value1["bairro"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-rua="<?php echo htmlspecialchars( $value1["rua"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-numero="<?php echo htmlspecialchars( $value1["numero"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-referencia="<?php echo htmlspecialchars( $value1["referencia"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-nacionalidade="<?php echo htmlspecialchars( $value1["nacionalidade"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-naturalidade="<?php echo htmlspecialchars( $value1["naturalidade"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"
                        data-tempo="<?php echo htmlspecialchars( $value1["tempo_moradia"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" data-cidade="<?php echo htmlspecialchars( $value1["cidade"], ENT_COMPAT, 'UTF-8', FALSE ); ?>">
                        <i class="bi bi-eye"></i>
                      </button>

                      <?php if( canAccess('CLIENTES_UPDATE') ){ ?>

                      <a href="/admin/clientes/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" class="btn btn-warning btn-sm" title="Editar">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <?php } ?>


                      <?php if( canAccess('CLIENTES_DELETE') ){ ?>

                      <form action="/admin/clientes/<?php echo htmlspecialchars( $value1["id"], ENT_COMPAT, 'UTF-8', FALSE ); ?>/delete" method="post" class="d-inline"
                        onsubmit="return confirm('Deseja realmente excluir este registro?')">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars( $csrf_token, ENT_COMPAT, 'UTF-8', FALSE ); ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                      <?php } ?>

                    </div>
                  </td>
                </tr>
                <?php } ?>

              </tbody>
            </table>
          </div>

          <div class="card-footer bg-white border-0 d-flex gap-2 flex-wrap">
            <a href="/admin/clientes/create" class="btn btn-primary btn-sm">
              <i class="bi bi-person-plus me-1"></i> NOVO TITULAR
            </a>

            <a href="/admin/clientes/vulnerabilidade/create" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-person-heart me-1"></i> NOVO VULNERABILIDADE
            </a>

            <a href="/admin/dependente/create" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-people me-1"></i> NOVO DEPENDENTE
            </a>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL RELATÓRIO -->
<div class="modal fade" id="modalRelatorio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-dialog-premium">
    <div class="modal-content border-0 shadow-lg modal-titular-premium">

      <div class="modal-header modal-profile-header border-0">
        <div class="modal-profile-wrap w-100">
          <div class="modal-profile-main">
            <div class="modal-profile-avatar" id="perfilIniciais">--</div>

            <div class="modal-profile-content">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                  <h4 class="modal-profile-name mb-1" id="perfilNomeTopo">Titular</h4>
                  <div class="modal-profile-meta">
                    <span class="perfil-chip">
                      <i class="bi bi-hash me-1"></i>
                      ID <span id="perfilIdTopo"></span>
                    </span>
                    <span class="perfil-chip">
                      <i class="bi bi-card-text me-1"></i>
                      CPF <span id="perfilCpfTopo"></span>
                    </span>
                    <span class="perfil-chip perfil-chip-status" id="perfilStatusTopo">
                      <i class="bi bi-shield-check me-1"></i>
                      Status
                    </span>
                  </div>
                </div>

                <button type="button" class="btn-close btn-close-white modal-close-premium" data-bs-dismiss="modal"
                  aria-label="Fechar"></button>
              </div>

              <p class="modal-profile-subtitle mb-0">
                Visualize os dados completos do titular, endereço e dependentes vinculados.
              </p>
            </div>
          </div>

          <div class="modal-profile-summary row g-3 mt-1">
            <div class="col-12 col-md-6 col-xl-3">
              <div class="summary-card summary-card-blue">
                <div class="summary-card-icon">
                  <i class="bi bi-telephone"></i>
                </div>
                <div class="summary-card-content">
                  <span class="summary-card-label">Telefone</span>
                  <strong class="summary-card-value" id="resumoTelefone">-</strong>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="summary-card summary-card-green">
                <div class="summary-card-icon">
                  <i class="bi bi-calendar2-heart"></i>
                </div>
                <div class="summary-card-content">
                  <span class="summary-card-label">Idade</span>
                  <strong class="summary-card-value" id="resumoIdade">-</strong>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="summary-card summary-card-cyan">
                <div class="summary-card-icon">
                  <i class="bi bi-geo-alt"></i>
                </div>
                <div class="summary-card-content">
                  <span class="summary-card-label">Cidade</span>
                  <strong class="summary-card-value" id="resumoCidade">-</strong>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
              <div class="summary-card summary-card-yellow">
                <div class="summary-card-icon">
                  <i class="bi bi-people"></i>
                </div>
                <div class="summary-card-content">
                  <span class="summary-card-label">Dependentes</span>
                  <strong class="summary-card-value" id="resumoDependentes">0</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-body premium-modal-body">
        <ul class="nav nav-tabs premium-tabs mb-4" id="modalTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-dados-tab" data-bs-toggle="tab" data-bs-target="#tab-dados"
              type="button" role="tab">
              <i class="bi bi-person-badge me-2"></i> Dados Pessoais
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-endereco-tab" data-bs-toggle="tab" data-bs-target="#tab-endereco"
              type="button" role="tab">
              <i class="bi bi-geo-alt-fill me-2"></i> Endereço
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-dependentes-tab" data-bs-toggle="tab" data-bs-target="#tab-dependentes"
              type="button" role="tab">
              <i class="bi bi-people-fill me-2"></i> Dependentes
            </button>
          </li>
        </ul>

        <div class="tab-content" id="modalTabsContent">

          <div class="tab-pane fade show active" id="tab-dados" role="tabpanel">
            <div class="row g-4">
              <div class="col-12 col-lg-6">
                <div class="premium-section-card premium-section-blue h-100">
                  <div class="premium-section-header">
                    <div>
                      <h5 class="premium-section-title mb-1">
                        <i class="bi bi-person-vcard me-2"></i> Informações Pessoais
                      </h5>
                      <p class="premium-section-subtitle mb-0">Dados principais do titular</p>
                    </div>
                  </div>

                  <div class="premium-info-grid">
                    <div class="premium-info-item">
                      <span class="premium-info-label">ID</span>
                      <span class="premium-info-value" id="m_id"></span>
                    </div>

                    <div class="premium-info-item premium-info-item-full">
                      <span class="premium-info-label">Nome Completo</span>
                      <span class="premium-info-value" id="m_nome"></span>
                    </div>

                    <div class="premium-info-item premium-info-item-full">
                      <span class="premium-info-label">Nome Social</span>
                      <span class="premium-info-value" id="m_social"></span>
                    </div>

                    <div class="premium-info-item premium-info-item-full">
                      <span class="premium-info-label">Nome da Mãe</span>
                      <span class="premium-info-value" id="m_mae"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Cor / Etnia</span>
                      <span class="premium-info-value" id="m_cor"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Estado Civil</span>
                      <span class="premium-info-value" id="m_estado"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Telefone</span>
                      <span class="premium-info-value" id="m_telefone"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Data de Nascimento</span>
                      <span class="premium-info-value" id="m_nascimento"></span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-6">
                <div class="premium-section-card premium-section-green h-100">
                  <div class="premium-section-header">
                    <div>
                      <h5 class="premium-section-title mb-1">
                        <i class="bi bi-file-earmark-lock2 me-2"></i> Documentos & Cadastro
                      </h5>
                      <p class="premium-section-subtitle mb-0">Documentos e situação cadastral</p>
                    </div>
                  </div>

                  <div class="premium-info-grid">
                    <div class="premium-info-item">
                      <span class="premium-info-label">RG</span>
                      <span class="premium-info-value" id="m_rg"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">CPF</span>
                      <span class="premium-info-value" id="m_cpf"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Sexo</span>
                      <span class="premium-info-value" id="m_sexo"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Status</span>
                      <span class="premium-info-value">
                        <span class="status-pill" id="m_status"></span>
                      </span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">NIS</span>
                      <span class="premium-info-value" id="m_nis"></span>
                    </div>

                    <div class="premium-info-item">
                      <span class="premium-info-label">Idade</span>
                      <span class="premium-info-value" id="m_idade"></span>
                    </div>

                    <div class="premium-info-item premium-info-item-full">
                      <span class="premium-info-label">Naturalidade</span>
                      <span class="premium-info-value" id="m_naturalidade"></span>
                    </div>

                    <div class="premium-info-item premium-info-item-full">
                      <span class="premium-info-label">Nacionalidade</span>
                      <span class="premium-info-value" id="m_nacionalidade"></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-endereco" role="tabpanel">
            <div class="premium-section-card premium-section-cyan">
              <div class="premium-section-header">
                <div>
                  <h5 class="premium-section-title mb-1">
                    <i class="bi bi-geo-fill me-2"></i> Endereço do Titular
                  </h5>
                  <p class="premium-section-subtitle mb-0">Localização e referências cadastrais</p>
                </div>
              </div>

              <div class="endereco-grid">
                <div class="endereco-card">
                  <span class="endereco-label">CEP</span>
                  <strong class="endereco-value" id="m_cep"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Bairro</span>
                  <strong class="endereco-value" id="m_bairro"></strong>
                </div>

                <div class="endereco-card endereco-card-wide">
                  <span class="endereco-label">Rua</span>
                  <strong class="endereco-value" id="m_rua"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Número</span>
                  <strong class="endereco-value" id="m_numero"></strong>
                </div>

                <div class="endereco-card endereco-card-wide">
                  <span class="endereco-label">Referência</span>
                  <strong class="endereco-value" id="m_referencia"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Cidade</span>
                  <strong class="endereco-value" id="m_cidade"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Tempo de Moradia</span>
                  <strong class="endereco-value" id="m_tempo"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Naturalidade</span>
                  <strong class="endereco-value" id="m_naturalidade_endereco"></strong>
                </div>

                <div class="endereco-card">
                  <span class="endereco-label">Nacionalidade</span>
                  <strong class="endereco-value" id="m_nacionalidade_endereco"></strong>
                </div>
              </div>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-dependentes" role="tabpanel">
            <div class="dependentes-panel">
              <div class="dependentes-panel-header">
                <div>
                  <h6 class="dependentes-title mb-1">
                    <i class="bi bi-people-fill me-2"></i> Dependentes
                  </h6>
                  <p class="dependentes-subtitle mb-0">
                    Lista de dependentes vinculados ao titular selecionado.
                  </p>
                </div>
                <span class="dependentes-badge" id="dependentesTotalBadge">0 dependentes</span>
              </div>

              <div id="m_dependentes">
                <div class="dependentes-empty">
                  <i class="bi bi-people"></i>
                  <p class="mb-0">Selecione um titular para visualizar os dependentes.</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer border-0 premium-modal-footer">
        <button type="button" class="btn btn-light premium-btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Fechar
        </button>
      </div>

    </div>
  </div>
</div>

<!-- MODAL EDITAR DEPENDENTE -->
<div class="modal fade" id="modalEditarDependente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
  data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content edit-modal-premium border-0 shadow-lg">
      <div class="modal-header edit-modal-header border-0">
        <div>
          <h5 class="modal-title fw-bold mb-1">
            <i class="bi bi-pencil-square me-2"></i> Editar Dependente
          </h5>
          <p class="mb-0 small opacity-75">Atualize os dados do dependente selecionado</p>
        </div>
        <button type="button" class="btn-close btn-close-white" aria-label="Fechar"
          onclick="fecharModalDependente()"></button>
      </div>

      <div class="modal-body p-4">
        <input type="hidden" id="editIdDependente">

        <div id="dependenteAlert" class="d-none"></div>

        <div class="row g-3">
          <div class="col-12">
            <div class="edit-field-group">
              <label class="form-label">Nome</label>
              <div class="input-icon-wrap">
                <i class="bi bi-person"></i>
                <input type="text" id="editNome" class="form-control premium-input">
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="edit-field-group">
              <label class="form-label">Parentesco</label>
              <div class="input-icon-wrap">
                <i class="bi bi-diagram-3"></i>
                <input type="text" id="editParentesco" class="form-control premium-input">
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="edit-field-group">
              <label class="form-label">Idade</label>
              <div class="input-icon-wrap">
                <i class="bi bi-calendar3"></i>
                <input type="number" id="editIdade" class="form-control premium-input">
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="edit-field-group">
              <label class="form-label">Sexo</label>
              <div class="input-icon-wrap">
                <i class="bi bi-gender-ambiguous"></i>
                <select id="editGenero" class="form-control premium-input">
                  <option value="">Selecione</option>
                  <option value="M">Masculino</option>
                  <option value="F">Feminino</option>
                  <option value="Outro">Outro</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-light premium-btn-secondary" onclick="fecharModalDependente()">
          Cancelar
        </button>
        <button type="button" class="btn btn-primary premium-btn-primary" id="btnSalvarEdicao">
          <i class="bi bi-check2-circle me-1"></i> Salvar Alterações
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let titularAtual = null;
  let modalEditarDependenteInstance = null;

  function exibirMensagemDependente(tipo, mensagem) {
    const box = $('#dependenteAlert');
    box.removeClass('d-none alert-success alert-danger alert-warning alert-info')
      .addClass('alert alert-' + tipo + ' premium-alert')
      .html(mensagem);
  }

  function limparMensagemDependente() {
    $('#dependenteAlert')
      .removeClass('alert alert-success alert-danger alert-warning alert-info premium-alert')
      .addClass('d-none')
      .html('');
  }

  function fecharModalDependente() {
    const modalEl = document.getElementById('modalEditarDependente');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
      modal.hide();
    } else {
      limparEstadoModalTravado();
    }

    limparEstadoModalTravado();
  }

  function limparEstadoModalTravado() {
    setTimeout(function () {
      if ($('.modal.show').length > 0) {
        return;
      }

      $('.modal-backdrop').remove();
      $('body')
        .removeClass('modal-open')
        .css('overflow', '')
        .css('padding-right', '');

      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('padding-right');
    }, 350);
  }

  function normalizarValor(valor) {
    const texto = (valor || '').toString().trim();
    return texto !== '' ? texto : '-';
  }

  function getIniciaisNome(nome) {
    if (!nome) return '--';

    const partes = nome.trim().split(/\s+/).filter(Boolean);

    if (partes.length === 1) {
      return partes[0].substring(0, 2).toUpperCase();
    }

    return (partes[0][0] + partes[1][0]).toUpperCase();
  }

  function escaparHtml(texto) {
    return String(texto || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatarGenero(genero) {
    if (genero === 'M') return 'Masculino';
    if (genero === 'F') return 'Feminino';
    if (genero === 'Outro') return 'Outro';
    if (genero === 'Masculino') return 'Masculino';
    if (genero === 'Feminino') return 'Feminino';
    return normalizarValor(genero);
  }

  function aplicarStatusVisual(status) {
    const valor = normalizarValor(status);
    $('#m_status').text(valor);
    $('#perfilStatusTopo').html('<i class="bi bi-shield-check me-1"></i>' + valor);
  }

  function preencherTopoPerfil(button) {
    const nome = normalizarValor(button.dataset.nome);
    const cpf = normalizarValor(button.dataset.cpf);
    const status = normalizarValor(button.dataset.status);
    const id = normalizarValor(button.dataset.id);
    const telefone = normalizarValor(button.dataset.telefone);
    const idade = normalizarValor(button.dataset.idade);
    const cidade = normalizarValor(button.dataset.cidade);

    $('#perfilNomeTopo').text(nome);
    $('#perfilCpfTopo').text(cpf);
    $('#perfilIdTopo').text(id);
    $('#perfilIniciais').text(getIniciaisNome(nome));
    $('#resumoTelefone').text(telefone);
    $('#resumoIdade').text(idade);
    $('#resumoCidade').text(cidade);
    $('#resumoDependentes').text('0');
    $('#perfilStatusTopo').html('<i class="bi bi-shield-check me-1"></i>' + status);
  }

  function preencherDadosTitular(button) {
    if (!button) return;

    $('#m_id').text(normalizarValor(button.dataset.id));
    $('#m_nome').text(normalizarValor(button.dataset.nome));
    $('#m_social').text(normalizarValor(button.dataset.social));
    $('#m_mae').text(normalizarValor(button.dataset.mae));
    $('#m_cor').text(normalizarValor(button.dataset.cor));
    $('#m_telefone').text(normalizarValor(button.dataset.telefone));
    $('#m_estado').text(normalizarValor(button.dataset.estado));
    $('#m_rg').text(normalizarValor(button.dataset.rg));
    $('#m_cpf').text(normalizarValor(button.dataset.cpf));
    $('#m_sexo').text(normalizarValor(button.dataset.sexo));
    $('#m_nis').text(normalizarValor(button.dataset.nis));
    $('#m_nascimento').text(normalizarValor(button.dataset.nascimento));
    $('#m_idade').text(normalizarValor(button.dataset.idade));

    $('#m_cep').text(normalizarValor(button.dataset.cep));
    $('#m_bairro').text(normalizarValor(button.dataset.bairro));
    $('#m_rua').text(normalizarValor(button.dataset.rua));
    $('#m_numero').text(normalizarValor(button.dataset.numero));
    $('#m_referencia').text(normalizarValor(button.dataset.referencia));
    $('#m_nacionalidade').text(normalizarValor(button.dataset.nacionalidade));
    $('#m_naturalidade').text(normalizarValor(button.dataset.naturalidade));
    $('#m_tempo').text(normalizarValor(button.dataset.tempo));
    $('#m_cidade').text(normalizarValor(button.dataset.cidade));

    $('#m_naturalidade_endereco').text(normalizarValor(button.dataset.naturalidade));
    $('#m_nacionalidade_endereco').text(normalizarValor(button.dataset.nacionalidade));

    aplicarStatusVisual(button.dataset.status);
    preencherTopoPerfil(button);

    titularAtual = button.dataset.id || null;
  }

  function renderDependentes(deps) {
    const container = $('#m_dependentes');
    const badge = $('#dependentesTotalBadge');

    const total = Array.isArray(deps) ? deps.length : 0;
    badge.text(total + (total === 1 ? ' dependente' : ' dependentes'));
    $('#resumoDependentes').text(total);

    if (!deps || deps.length === 0) {
      container.html(`
        <div class="dependentes-empty">
          <i class="bi bi-person-x"></i>
          <p class="mb-1 fw-semibold">Nenhum dependente cadastrado</p>
          <small class="text-muted">Este titular ainda não possui dependentes vinculados.</small>
        </div>
      `);
      return;
    }

    let html = '<div class="dependentes-grid">';

    deps.forEach(function (d) {
      const nome = escaparHtml(d.nome ?? '-');
      const parentesco = escaparHtml(d.dependencia_cliente ?? '-');
      const idade = escaparHtml(d.idade ?? '-');
      const sexo = escaparHtml(formatarGenero(d.genero));
      const iniciais = escaparHtml(getIniciaisNome(d.nome ?? ''));

      html += `
        <div class="dependente-card">
          <div class="dependente-card-top">
            <div class="dependente-identidade">
              <div class="dependente-avatar">${iniciais}</div>

              <div class="dependente-info-principal">
                <h6 class="dependente-nome mb-1">${nome}</h6>
                <div class="dependente-tags">
                  <span class="dependente-tag">
                    <i class="bi bi-diagram-3 me-1"></i>${parentesco}
                  </span>
                  <span class="dependente-tag">
                    <i class="bi bi-calendar3 me-1"></i>${idade} anos
                  </span>
                  <span class="dependente-tag">
                    <i class="bi bi-gender-ambiguous me-1"></i>${sexo}
                  </span>
                </div>
              </div>
            </div>

            <div class="dependente-acoes">
              <button class="btn btn-primary btn-sm dependente-btn" onclick="editarDependente(${d.id})">
                <i class="bi bi-pencil-square me-1"></i> Editar
              </button>

              <button class="btn btn-outline-danger btn-sm dependente-btn" onclick="excluirDependente(${d.id})">
                <i class="bi bi-trash me-1"></i> Excluir
              </button>
            </div>
          </div>

          <div class="dependente-meta">
            <div class="dependente-meta-item">
              <span class="dependente-meta-label">Nome</span>
              <span class="dependente-meta-value">${nome}</span>
            </div>
            <div class="dependente-meta-item">
              <span class="dependente-meta-label">Parentesco</span>
              <span class="dependente-meta-value">${parentesco}</span>
            </div>
            <div class="dependente-meta-item">
              <span class="dependente-meta-label">Idade</span>
              <span class="dependente-meta-value">${idade}</span>
            </div>
            <div class="dependente-meta-item">
              <span class="dependente-meta-label">Sexo</span>
              <span class="dependente-meta-value">${sexo}</span>
            </div>
          </div>
        </div>
      `;
    });

    html += '</div>';
    container.html(html);
  }

  function carregarDependentes() {
    const container = $('#m_dependentes');

    if (!titularAtual) {
      container.html(`
        <div class="dependentes-empty">
          <i class="bi bi-people"></i>
          <p class="mb-0">Selecione um titular para visualizar os dependentes.</p>
        </div>
      `);
      $('#dependentesTotalBadge').text('0 dependentes');
      $('#resumoDependentes').text('0');
      return;
    }

    container.html(`
      <div class="dependentes-grid">
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
        <div class="skeleton-card"></div>
      </div>
    `);

    $.ajax({
      url: '/admin/dependentes/ajax/' + titularAtual,
      method: 'GET',
      dataType: 'json',
      cache: false,
      success: function (resp) {
        if (Array.isArray(resp)) {
          renderDependentes(resp);
          return;
        }

        if (resp && resp.success === true) {
          renderDependentes(resp.data || []);
          return;
        }

        $('#dependentesTotalBadge').text('0 dependentes');
        $('#resumoDependentes').text('0');
        container.html(`
          <div class="alert alert-warning mb-0">
            Nenhum dado retornado.
          </div>
        `);
      },
      error: function (xhr) {
        console.error('Erro ao carregar dependentes:', xhr.status, xhr.responseText);
        $('#dependentesTotalBadge').text('0 dependentes');
        $('#resumoDependentes').text('0');
        container.html(`
          <div class="alert alert-danger mb-0">
            Erro ao carregar dependentes.<br>
            <small>Status: ${xhr.status}</small>
          </div>
        `);
      }
    });
  }

  function editarDependente(idDep) {
    limparMensagemDependente();

    $.ajax({
      url: '/admin/dependentes/get/' + idDep,
      method: 'GET',
      dataType: 'json',
      success: function (resp) {
        if (resp.success === false) {
          alert(resp.message || 'Erro ao carregar dependente.');
          return;
        }

        const dep = resp.data ? resp.data : resp;

        $('#editIdDependente').val(dep.id || '');
        $('#editNome').val(dep.nome || '');
        $('#editParentesco').val(dep.dependencia_cliente || '');
        $('#editIdade').val(dep.idade || '');

        if (dep.genero === 'Masculino') {
          $('#editGenero').val('M');
        } else if (dep.genero === 'Feminino') {
          $('#editGenero').val('F');
        } else {
          $('#editGenero').val(dep.genero || '');
        }

        $('#editNome, #editParentesco, #editIdade, #editGenero')
          .prop('disabled', false)
          .prop('readonly', false);

        const modalEl = document.getElementById('modalEditarDependente');
        const modalRelatorioEl = document.getElementById('modalRelatorio');
        const modalRelatorio = bootstrap.Modal.getInstance(modalRelatorioEl);

        function abrirModalEdicaoDependente() {
          if (!modalEditarDependenteInstance) {
            modalEditarDependenteInstance = new bootstrap.Modal(modalEl, {
              backdrop: 'static',
              keyboard: false,
              focus: true
            });
          }

          modalEditarDependenteInstance.show();

          setTimeout(function () {
            $('#editNome').trigger('focus');
          }, 150);
        }

        if ($('#modalRelatorio').hasClass('show') && modalRelatorio) {
          $(modalRelatorioEl).one('hidden.bs.modal', abrirModalEdicaoDependente);
          modalRelatorio.hide();
          return;
        }

        abrirModalEdicaoDependente();
      },
      error: function (xhr) {
        console.error("Erro ao carregar dependente:", xhr.status, xhr.responseText);
        alert("Erro ao carregar dependente!");
      }
    });
  }

  function excluirDependente(idDep) {
    if (!confirm("Tem certeza que deseja excluir este dependente?")) return;

    $.ajax({
      url: '/admin/dependentes/excluir/' + idDep,
      method: 'DELETE',
      dataType: 'json',
      success: function (resp) {
        alert(resp.message || "Dependente excluído com sucesso!");
        carregarDependentes();
      },
      error: function (xhr) {
        console.error("Erro ao excluir dependente:", xhr.status, xhr.responseText);
        alert("Erro ao excluir dependente!");
      }
    });
  }

  $(document).ready(function () {
    $('#tabela_titulares').DataTable({
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
      },
      paging: true,
      searching: true,
      ordering: true,
      info: true,
      pageLength: 10
    });

    $('#modalRelatorio').on('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      preencherDadosTitular(button);
    });

    $('#modalRelatorio').on('shown.bs.modal', function () {
      if ($('#tab-dependentes-tab').hasClass('active')) {
        carregarDependentes();
      }
    });

    $('#tab-dependentes-tab').on('shown.bs.tab', function () {
      carregarDependentes();
    });

    $('#modalRelatorio').on('hidden.bs.modal', function () {
      titularAtual = null;

      $('#perfilNomeTopo').text('Titular');
      $('#perfilCpfTopo').text('');
      $('#perfilIdTopo').text('');
      $('#perfilIniciais').text('--');
      $('#resumoTelefone').text('-');
      $('#resumoIdade').text('-');
      $('#resumoCidade').text('-');
      $('#resumoDependentes').text('0');

      $('#dependentesTotalBadge').text('0 dependentes');
      $('#m_dependentes').html(`
        <div class="dependentes-empty">
          <i class="bi bi-people"></i>
          <p class="mb-0">Selecione um titular para visualizar os dependentes.</p>
        </div>
      `);

      const abaDados = new bootstrap.Tab(document.getElementById('tab-dados-tab'));
      abaDados.show();

      limparEstadoModalTravado();
    });

    $('#modalEditarDependente').on('hidden.bs.modal', function () {
      limparMensagemDependente();
      limparEstadoModalTravado();
    });

    $('#btnSalvarEdicao').on('click', function () {
      const idDep = $('#editIdDependente').val();

      const data = {
        nome: $('#editNome').val(),
        dependencia_cliente: $('#editParentesco').val(),
        idade: $('#editIdade').val(),
        genero: $('#editGenero').val()
      };

      $.ajax({
        url: '/admin/dependentes/editar/' + idDep,
        method: 'POST',
        headers: {
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        data: JSON.stringify(data),
        contentType: 'application/json',
        dataType: 'json',
        success: function (resp) {
          if (resp.success === false) {
            exibirMensagemDependente('danger', resp.message || resp.error || 'Erro ao atualizar dependente.');
            return;
          }

          exibirMensagemDependente('success', resp.message || 'Dependente atualizado com sucesso.');

          if ($('#modalRelatorio').hasClass('show') && titularAtual) {
            carregarDependentes();
          }

          setTimeout(function () {
            fecharModalDependente();
          }, 1200);
        },
        error: function (xhr) {
          console.error("Erro ao salvar dependente:", xhr.status, xhr.responseText);
          exibirMensagemDependente('danger', 'Erro ao salvar dependente.');
        }
      });
    });
  });
</script>

<style>
  .clientes-page-card {
    border-radius: 18px;
    overflow: hidden;
  }

  .clientes-page-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    border-bottom: 1px solid #eef2f7;
    padding: 18px 20px;
  }

  .clientes-table thead th {
    white-space: nowrap;
    font-weight: 700;
    font-size: .86rem;
  }

  .modal-dialog-premium {
    max-width: 1280px;
  }

  .modal-titular-premium {
    border-radius: 28px;
    overflow: hidden;
    background: #fff;
  }

  .modal-profile-header {
    background: linear-gradient(135deg, #0d6efd 0%, #1f7dff 45%, #4dabf7 100%);
    color: #fff;
    padding: 28px 28px 22px;
  }

  .modal-profile-main {
    display: flex;
    gap: 18px;
    align-items: flex-start;
  }

  .modal-profile-avatar {
    width: 86px;
    height: 86px;
    min-width: 86px;
    border-radius: 24px;
    background: rgba(255, 255, 255, .18);
    border: 1px solid rgba(255, 255, 255, .22);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    font-weight: 800;
    box-shadow: 0 10px 30px rgba(0, 0, 0, .15);
  }

  .modal-profile-content {
    flex: 1;
  }

  .modal-profile-name {
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.2;
    margin-right: 12px;
  }

  .modal-profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
  }

  .perfil-chip {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, .15);
    border: 1px solid rgba(255, 255, 255, .2);
    font-size: .84rem;
    font-weight: 600;
  }

  .perfil-chip-status {
    background: rgba(25, 135, 84, .18);
  }

  .modal-profile-subtitle {
    color: rgba(255, 255, 255, .88);
    font-size: .95rem;
  }

  .modal-close-premium {
    opacity: .9;
  }

  .modal-profile-summary {
    margin-top: 22px !important;
  }

  .summary-card {
    border-radius: 20px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 90px;
    background: rgba(255, 255, 255, .12);
    border: 1px solid rgba(255, 255, 255, .16);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
  }

  .summary-card-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    background: rgba(255, 255, 255, .18);
  }

  .summary-card-label {
    display: block;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    opacity: .9;
  }

  .summary-card-value {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.25;
    margin-top: 2px;
    word-break: break-word;
  }

  .summary-card-blue,
  .summary-card-green,
  .summary-card-cyan,
  .summary-card-yellow {
    color: #fff;
  }

  .premium-modal-body {
    background: linear-gradient(180deg, #f7faff 0%, #f9fbfd 100%);
    padding: 26px;
  }

  .premium-tabs {
    border-bottom: none;
    gap: 10px;
    flex-wrap: wrap;
  }

  .premium-tabs .nav-link {
    border: none;
    border-radius: 14px;
    padding: 12px 18px;
    background: #edf3fb;
    color: #526070;
    font-weight: 700;
    transition: all .2s ease;
  }

  .premium-tabs .nav-link:hover {
    background: #e4edf8;
    color: #24324a;
  }

  .premium-tabs .nav-link.active {
    background: #fff;
    color: #0d6efd;
    box-shadow: 0 8px 20px rgba(13, 110, 253, .10);
  }

  .premium-section-card {
    background: #fff;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    border: 1px solid #edf2f7;
  }

  .premium-section-blue {
    border-top: 4px solid #0d6efd;
  }

  .premium-section-green {
    border-top: 4px solid #198754;
  }

  .premium-section-cyan {
    border-top: 4px solid #0dcaf0;
  }

  .premium-section-header {
    margin-bottom: 18px;
  }

  .premium-section-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #24324a;
  }

  .premium-section-subtitle {
    font-size: .92rem;
    color: #6b7280;
  }

  .premium-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(160px, 1fr));
    gap: 14px;
  }

  .premium-info-item {
    background: #f8fbff;
    border: 1px solid #e9f0f8;
    border-radius: 16px;
    padding: 14px 16px;
    min-height: 78px;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .premium-info-item-full {
    grid-column: 1 / -1;
  }

  .premium-info-label {
    display: block;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6c757d;
    font-weight: 700;
    margin-bottom: 6px;
  }

  .premium-info-value {
    display: block;
    font-size: 1rem;
    color: #1f2937;
    font-weight: 700;
    word-break: break-word;
  }

  .status-pill {
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #e8fff2;
    color: #0f7b43;
    border: 1px solid #cdeedb;
    font-size: .86rem;
    font-weight: 700;
  }

  .endereco-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 14px;
  }

  .endereco-card {
    background: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
    border: 1px solid #e7f1f7;
    border-radius: 18px;
    padding: 16px;
    min-height: 92px;
    box-shadow: 0 6px 18px rgba(13, 202, 240, 0.05);
  }

  .endereco-card-wide {
    grid-column: span 2;
  }

  .endereco-label {
    display: block;
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6c757d;
    font-weight: 700;
    margin-bottom: 6px;
  }

  .endereco-value {
    display: block;
    color: #233247;
    font-size: 1rem;
    font-weight: 700;
    word-break: break-word;
  }

  .dependentes-panel {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  }

  .dependentes-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #eef2f7;
  }

  .dependentes-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #d39e00;
  }

  .dependentes-subtitle {
    color: #6c757d;
    font-size: .93rem;
  }

  .dependentes-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 999px;
    background: #fff8e1;
    color: #9a6700;
    font-weight: 700;
    font-size: .88rem;
    border: 1px solid #ffe08a;
  }

  .dependentes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }

  .dependente-card {
    background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
    border: 1px solid #e8edf3;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    transition: all .2s ease;
  }

  .dependente-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    border-color: #d8e2ee;
  }

  .dependente-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
  }

  .dependente-identidade {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    flex: 1;
    min-width: 280px;
  }

  .dependente-avatar {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #0d6efd, #4dabf7);
    color: #fff;
    font-weight: 800;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 18px rgba(13, 110, 253, 0.22);
  }

  .dependente-nome {
    font-size: 1.05rem;
    font-weight: 800;
    color: #24324a;
  }

  .dependente-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .dependente-tag {
    display: inline-flex;
    align-items: center;
    background: #f4f7fb;
    color: #495057;
    border: 1px solid #e4ebf3;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: .83rem;
    font-weight: 700;
  }

  .dependente-acoes {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 130px;
  }

  .dependente-btn {
    border-radius: 10px;
    font-weight: 700;
    padding: 8px 12px;
  }

  .dependente-meta {
    margin-top: 16px;
    padding-top: 14px;
    border-top: 1px dashed #e3e8ef;
    display: grid;
    grid-template-columns: repeat(4, minmax(120px, 1fr));
    gap: 12px;
  }

  .dependente-meta-item {
    background: #f8fafc;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    padding: 10px 12px;
  }

  .dependente-meta-label {
    display: block;
    font-size: .74rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
    font-weight: 700;
  }

  .dependente-meta-value {
    display: block;
    color: #24324a;
    font-weight: 700;
    word-break: break-word;
  }

  .dependentes-empty {
    min-height: 180px;
    border: 2px dashed #d9e2ec;
    border-radius: 16px;
    background: #fafcff;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #7b8794;
    text-align: center;
    padding: 24px;
  }

  .dependentes-empty i {
    font-size: 2.2rem;
    margin-bottom: 10px;
    color: #b0bac5;
  }

  .skeleton-card {
    height: 170px;
    width: 100%;
    border-radius: 16px;
    background: linear-gradient(90deg, #eef2f7 25%, #dde5ee 50%, #eef2f7 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border: 1px solid #e8edf3;
  }

  .edit-modal-premium {
    border-radius: 24px;
    overflow: hidden;
  }

  .edit-modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #3b82f6 100%);
    color: #fff;
    padding: 22px 24px;
  }

  .edit-field-group .form-label {
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
  }

  .input-icon-wrap {
    position: relative;
  }

  .input-icon-wrap i {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #6b7280;
    z-index: 2;
    pointer-events: none;
  }

  .premium-input {
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid #dbe5f0;
    padding-left: 42px;
    box-shadow: none !important;
  }

  .premium-input:focus {
    border-color: #86b7fe;
  }

  .premium-btn-primary {
    border-radius: 12px;
    font-weight: 700;
    padding: 10px 18px;
  }

  .premium-btn-secondary {
    border-radius: 12px;
    font-weight: 700;
    padding: 10px 18px;
  }

  .premium-alert {
    border-radius: 14px;
    margin-bottom: 18px;
  }

  .premium-modal-footer {
    padding: 0 26px 24px;
    background: #fff;
  }

  @keyframes shimmer {
    0% {
      background-position: -200% 0;
    }

    100% {
      background-position: 200% 0;
    }
  }

  @media (max-width: 1199px) {
    .endereco-grid {
      grid-template-columns: repeat(2, minmax(120px, 1fr));
    }

    .endereco-card-wide {
      grid-column: span 2;
    }
  }

  @media (max-width: 991px) {
    .premium-info-grid {
      grid-template-columns: 1fr;
    }

    .premium-info-item-full {
      grid-column: auto;
    }

    .dependente-meta {
      grid-template-columns: repeat(2, minmax(120px, 1fr));
    }

    .modal-profile-main {
      flex-direction: column;
    }
  }

  @media (max-width: 767px) {

    .premium-modal-body,
    .modal-profile-header {
      padding: 18px;
    }

    .modal-profile-avatar {
      width: 72px;
      height: 72px;
      min-width: 72px;
      font-size: 1.35rem;
    }

    .modal-profile-name {
      font-size: 1.2rem;
    }

    .premium-tabs .nav-link {
      width: 100%;
      justify-content: flex-start;
      text-align: left;
    }

    .endereco-grid {
      grid-template-columns: 1fr;
    }

    .endereco-card-wide {
      grid-column: auto;
    }

    .dependente-card {
      padding: 14px;
    }

    .dependente-card-top {
      flex-direction: column;
      align-items: stretch;
    }

    .dependente-identidade {
      min-width: 100%;
    }

    .dependente-acoes {
      width: 100%;
      min-width: 100%;
    }

    .dependente-btn {
      width: 100%;
    }

    .dependente-meta {
      grid-template-columns: 1fr;
    }
  }
</style>
