<?php if(!class_exists('Rain\Tpl')){exit;}?><style>
    :root {
        --bg: #f6f8fb;
        --surface: #ffffff;
        --text: #0f172a;
        --muted: #64748b;
        --border: #e5e7eb;
        --header: #f1f5f9;
        --accent: #1f3a8a;
        --shadow: 0 10px 25px rgba(15, 23, 42, .08);
    }

    body {
        background: var(--bg);
        color: var(--text);
    }

    .card {
        border: 1px solid var(--border) !important;
        border-radius: 16px !important;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .card-header {
        background: #fff;
        border-bottom: 1px solid var(--border);
    }

    .top-bar {
        background: var(--header);
        border: 1px solid var(--border);
        padding: 16px;
        border-radius: 14px;
        color: var(--text);
    }

    .top-bar label {
        font-weight: 600;
        color: var(--muted);
        font-size: .85rem;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        border-radius: 12px;
        border: 1px solid var(--border);
        background: #fff;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: rgba(14, 165, 233, .65);
        box-shadow: 0 0 0 4px rgba(14, 165, 233, .15);
    }

    .btn-primary {
        background: var(--accent) !important;
        border-color: var(--accent) !important;
        border-radius: 12px !important;
        font-weight: 600;
    }

    .btn-light {
        background: #fff !important;
        border: 1px solid var(--border) !important;
        border-radius: 12px !important;
        font-weight: 600;
        color: var(--text) !important;
    }

    .btn-soft {
        background: var(--accent) !important;
        border: 1px solid var(--accent) !important;
        color: #fff !important;
        border-radius: 12px !important;
        font-weight: 700;
    }

    .btn-soft:hover {
        filter: brightness(1.05);
    }

    .btn-outline-soft {
        background: #fff !important;
        border: 1px solid var(--border) !important;
        color: var(--text) !important;
        border-radius: 12px !important;
        font-weight: 700;
    }

    .btn-outline-soft:hover {
        background: #f8fafc !important;
    }

    .btn-pdf-premium {
        background: linear-gradient(135deg, #0f766e, #0ea5e9) !important;
        border: none !important;
        color: #fff !important;
        border-radius: 12px !important;
        font-weight: 700;
        box-shadow: 0 10px 18px rgba(14, 165, 233, .18);
    }

    .btn-pdf-premium:hover {
        filter: brightness(1.04);
        color: #fff !important;
    }

    .btn-pdf-premium[disabled] {
        opacity: .85;
        cursor: not-allowed;
    }

    .pdf-status {
        min-height: 18px;
        font-size: .8rem;
        color: var(--muted);
    }

    .kpi {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 14px 16px;
        box-shadow: var(--shadow);
        height: 100%;
    }

    .kpi .label {
        font-size: .8rem;
        color: var(--muted);
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .kpi .value {
        font-size: 2rem;
        font-weight: 900;
        margin-top: 6px;
    }

    .box {
        background: var(--surface);
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    .table thead th {
        background: #ffffff;
        border-bottom: 1px solid var(--border);
        font-size: .78rem;
        letter-spacing: .04em;
        color: var(--muted);
        text-transform: uppercase;
    }

    @media (max-width: 768px) {
        .top-bar .row>div {
            margin-bottom: 10px;
        }
    }
</style>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-center">
        <h3 class="card-title mb-0">RELATÓRIO DIÁRIO - SENHAS</h3>
    </div>

    <div class="app-content">
        <div class="container-fluid">

            <div class="top-bar mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:10px;">
                    <div>
                        <div style="font-weight:700; color:#0f172a;">Relatórios e exportação</div>
                        <div style="font-size:.85rem; color:#64748b;">
                            Consulta diária, geração de PDF e acesso ao histórico
                        </div>
                    </div>

                    <div class="d-flex flex-wrap" style="gap:8px;">
                        <a href="/admin/relatorio/pdf/historico" target="_blank" class="btn btn-outline-soft">
                            <i class="fa fa-history"></i> Histórico de PDFs
                        </a>
                    </div>
                </div>

                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label>Data</label>
                        <input type="date" id="relData" class="form-control" />
                    </div>

                    <div class="col-md-3">
                        <label>Tipo de Senha</label>
                        <select id="relTipoSenha" class="form-select">
                            <option value="">TODAS</option>
                            <option value="NORMAL">NORMAL</option>
                            <option value="GENERICA">GENÉRICA</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="button"
                            onclick="atualizarRelatorio()">Atualizar</button>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-light w-100" type="button" onclick="exportarCSV()">Exportar CSV</button>
                    </div>

                    <div class="col-md-2">
                        <button id="btnGerarPDFPremium" class="btn btn-pdf-premium w-100" type="button"
                            onclick="abrirRelatorioPDFPremium()">Gerar PDF</button>
                    </div>

                    <div class="col-12 mt-2">
                        <small id="relStatus" class="text-muted"></small>
                        <div id="statusGeracaoPDF" class="pdf-status mt-1" aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Total</div>
                        <div class="value" id="kpiTotal">0</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Pessoas em Situação de Rua</div>
                        <div class="value" id="kpiSituacaoRua">0</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Genérica</div>
                        <div class="value" id="kpiGenerica">0</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Dependentes</div>
                        <div class="value" id="kpiDependentes">0</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Titulares</div>
                        <div class="value" id="kpiTitulares">0</div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="kpi">
                        <div class="label">Deficientes</div>
                        <div class="value" id="kpiDeficientes">0</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="box p-2">
                        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                            <div class="small text-muted" id="listaInfo"></div>
                            <div class="d-flex align-items-center" style="gap:.5rem;">
                                <span class="small text-muted">Por página</span>
                                <select id="listaPageSize" class="form-select form-select-sm" style="width:auto;">
                                    <option value="25">25</option>
                                    <option value="50" selected>50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                </select>
                                <button id="listaPrev" class="btn btn-sm btn-outline-secondary"
                                    type="button">Anterior</button>
                                <span id="listaPageIndicator" class="small text-muted"></span>
                                <button id="listaNext" class="btn btn-sm btn-outline-secondary"
                                    type="button">Próxima</button>
                            </div>
                        </div>

                        <div class="table-responsive mt-2">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>CPF</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Def.</th>
                                        <th>Data/Hora</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyLista"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="box p-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div style="font-weight:800;">Top 10 Titulares (período)</div>
                            <div class="d-flex align-items-center" style="gap:.5rem;">
                                <select id="topPeriodo" class="form-select form-select-sm" style="width:auto;">
                                    <option value="DIA">Dia</option>
                                    <option value="SEMANA">Semana</option>
                                    <option value="MES">Mês</option>
                                    <option value="ANO">Ano</option>
                                </select>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Ordenar Top 10">
                                    <button id="btnOrdFreq" type="button" class="btn btn-outline-secondary active"
                                        onclick="setOrdenacaoTop('FREQ')">Frequência</button>
                                    <button id="btnOrdRef" type="button" class="btn btn-outline-secondary"
                                        onclick="setOrdenacaoTop('REFEICOES')">Refeições</button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:36px;">#</th>
                                        <th>TITULAR</th>
                                        <th style="width:90px; text-align:right;">FREQ.</th>
                                        <th style="width:90px; text-align:right;">REFEIÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyTop10"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="box p-2">
                        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
                            <div style="font-weight:800;">Relatório mensal</div>
                            <div class="d-flex" style="gap:.5rem;">
                                <input type="month" id="relMes" class="form-control form-control-sm"
                                    style="width:auto;" />
                                <button class="btn btn-sm btn-primary" type="button"
                                    onclick="atualizarMensal()">Ver</button>
                            </div>
                        </div>
                        <div class="mt-2"><canvas id="chartMensal" height="180"></canvas></div>
                        <small class="text-muted">Total por dia (linhas em tb_senhas).</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    const API_REL = {
        resumo: "/admin/api/relatorio/senhas/resumo",
        lista: "/admin/api/relatorio/senhas/lista",
        export: "/admin/api/relatorio/senhas/export",
        top10: "/admin/api/relatorio/senhas/top10",
        mensal: "/admin/api/relatorio/senhas/mensal",
        pdf: "/admin/api/relatorio/pdf"
    };


    let page = 1;
    let pageSize = 50;
    let totalItems = 0;
    let chart = null;
    let topOrder = 'FREQ';

    function hojeISO() {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        return `${y}-${m}-${day}`;
    }

    function mesISO() {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, "0");
        return `${y}-${m}`;
    }

    function getFiltros() {
        const data = document.getElementById("relData")?.value || hojeISO();
        const tipoSenha = document.getElementById("relTipoSenha")?.value || "";
        return { data, tipoSenha };
    }

    function getPeriodoTop() {
        return document.getElementById('topPeriodo')?.value || 'DIA';
    }

    function getOrdenacaoTop() {
        return (topOrder || 'FREQ').toUpperCase();
    }

    function setOrdenacaoTop(valor) {
        topOrder = (valor || 'FREQ').toUpperCase();

        const bFreq = document.getElementById('btnOrdFreq');
        const bRef = document.getElementById('btnOrdRef');

        if (bFreq) bFreq.classList.toggle('active', topOrder === 'FREQ');
        if (bRef) bRef.classList.toggle('active', topOrder === 'REFEICOES');

        carregarTop10().catch(console.error);
    }

    function setStatus(msg) {
        const el = document.getElementById("relStatus");
        if (el) el.textContent = msg || "";
    }

    function montarQuery(params) {
        const q = new URLSearchParams();
        Object.entries(params).forEach(([k, v]) => {
            if (v !== undefined && v !== null && String(v) !== "") q.set(k, v);
        });
        return q.toString();
    }

    async function fetchJson(url) {
        const resp = await fetch(url, { headers: { "Accept": "application/json" } });
        const text = await resp.text();
        let data = null;
        try { data = JSON.parse(text); } catch (e) { }
        if (!resp.ok || !data || data.ok !== true) {
            const err = data?.error || `Falha (HTTP ${resp.status}).`;
            throw new Error(err);
        }
        return data;
    }

    async function carregarResumo() {
        const { data, tipoSenha } = getFiltros();
        const qs = montarQuery({ data, tipoSenha });
        const json = await fetchJson(`${API_REL.resumo}?${qs}`);

        const r = json.resumo || {};
        const totalRua = Number(r.situacao_risco_masculino || 0) + Number(r.situacao_risco_feminino || 0);

        document.getElementById("kpiTotal").textContent = r.total ?? 0;
        document.getElementById("kpiSituacaoRua").textContent = totalRua;
        document.getElementById("kpiGenerica").textContent = r.generica ?? 0;
        document.getElementById("kpiDependentes").textContent = r.dependentes ?? 0;
        document.getElementById("kpiTitulares").textContent = r.titulares ?? 0;
        document.getElementById("kpiDeficientes").textContent = r.deficientes ?? 0;
    }

    function renderLista(items) {
        const tbody = document.getElementById("tbodyLista");
        if (!tbody) return;

        if (!items || !items.length) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center">Sem registros</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map(it => {
            const dh = (it.registration_date || it.registration_date_update || '').toString();
            const def = (it.Deficiente ?? it.deficiente ?? '');
            return `
                <tr>
                    <td>${it.id ?? ''}</td>
                    <td>${it.cliente ?? ''}</td>
                    <td>${it.cpf ?? ''}</td>
                    <td>${it.tipoSenha ?? ''}</td>
                    <td>${it.status_cliente ?? ''}</td>
                    <td>${def}</td>
                    <td>${dh}</td>
                </tr>
            `;
        }).join("");
    }

    function atualizarPaginacao() {
        const totalPages = Math.max(1, Math.ceil((Number(totalItems) || 0) / (Number(pageSize) || 50)));
        if (page > totalPages) page = totalPages;
        if (page < 1) page = 1;

        const info = document.getElementById('listaInfo');
        const indicador = document.getElementById('listaPageIndicator');
        const prevBtn = document.getElementById('listaPrev');
        const nextBtn = document.getElementById('listaNext');

        const inicio = totalItems === 0 ? 0 : ((page - 1) * pageSize + 1);
        const fim = Math.min(page * pageSize, totalItems);

        if (info) info.textContent = totalItems === 0 ? '0 registro(s)' : `Mostrando ${inicio}-${fim} de ${totalItems}`;
        if (indicador) indicador.textContent = `Página ${page} de ${totalPages}`;
        if (prevBtn) prevBtn.disabled = page <= 1;
        if (nextBtn) nextBtn.disabled = page >= totalPages;
    }

    async function carregarLista() {
        const { data, tipoSenha } = getFiltros();
        const qs = montarQuery({ data, tipoSenha, page, pageSize });
        const json = await fetchJson(`${API_REL.lista}?${qs}`);
        totalItems = Number(json.total || 0);
        renderLista(json.items || []);
        atualizarPaginacao();
    }

    function renderTop10(items) {
        const tbody = document.getElementById('tbodyTop10');
        if (!tbody) return;

        if (!items || !items.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center">Sem dados</td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((it, idx) => {
            const nome = (it.titular_nome || it.nome || it.cliente || '').toString().toUpperCase();
            const cpf = (it.titular_cpf || it.cpf || '').toString();
            return `
                <tr>
                    <td>${idx + 1}</td>
                    <td>
                        <div style="font-weight:800;">${nome}</div>
                        <div class="text-muted" style="font-size:.8rem;">${cpf}</div>
                    </td>
                    <td style="text-align:right; font-weight:900;">${it.total_dias ?? 0}</td>
                    <td style="text-align:right; font-weight:900;">${it.total_refeicoes ?? 0}</td>
                </tr>
            `;
        }).join('');
    }

    async function carregarTop10() {
        const { data, tipoSenha } = getFiltros();
        const period = getPeriodoTop();
        const order = getOrdenacaoTop();
        const qs = montarQuery({ data, tipoSenha, period, order });
        const json = await fetchJson(`${API_REL.top10}?${qs}`);
        renderTop10(json.items || []);
    }

    async function atualizarMensal() {
        const mes = document.getElementById("relMes")?.value || mesISO();
        const qs = montarQuery({ mes });
        const json = await fetchJson(`${API_REL.mensal}?${qs}`);

        const items = json.items || [];
        const labels = items.map(i => (i.data_refeicao || '').slice(8, 10));
        const totals = items.map(i => Number(i.total || 0));

        const ctx = document.getElementById('chartMensal');
        if (!ctx) return;

        if (chart) chart.destroy();
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: `Total por dia (${mes})`,
                    data: totals,
                    tension: 0.25
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    async function atualizarRelatorio() {
        try {
            setStatus('Carregando...');
            page = 1;
            await carregarResumo();
            await carregarTop10();
            await carregarLista();
            setStatus('');
        } catch (e) {
            console.error(e);
            setStatus(e?.message || 'Erro ao carregar relatório.');
        }
    }

    function exportarCSV() {
        const { data, tipoSenha } = getFiltros();
        const qs = montarQuery({ data, tipoSenha });
        window.location.href = `${API_REL.export}?${qs}`;
    }


    let pdfPremiumEmAndamento = false;

    function setStatusGeracaoPDF(msg) {
        const el = document.getElementById("statusGeracaoPDF");
        if (el) el.textContent = msg || "";
    }

    function setEstadoBotaoPDF(ativo) {
        const btn = document.getElementById("btnGerarPDFPremium");
        if (!btn) return;

        btn.disabled = !!ativo;

        if (ativo) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Gerando PDF...';
        } else {
            btn.innerHTML = 'Gerar PDF';
        }
    }

    async function abrirRelatorioPDFPremium() {
        if (pdfPremiumEmAndamento) return;

        const { data } = getFiltros();

        const url = API_REL.pdf
            + "?data=" + encodeURIComponent(data)
            + "&upload=1";

        pdfPremiumEmAndamento = true;
        setEstadoBotaoPDF(true);
        setStatusGeracaoPDF("Preparando relatório em PDF...");

        try {
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            });

            const responseText = await response.text();

            let json = null;

            try {
                json = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error("Resposta inválida do servidor.");
            }

            if (!response.ok || !json.success) {
                throw new Error(json.message || json.error || "Erro ao gerar e enviar o PDF.");
            }

            if (!json.url_publica) {
                throw new Error("Upload concluído, mas a URL pública não foi retornada.");
            }

            window.open(json.url_publica, "_blank");
            setStatusGeracaoPDF("PDF enviado para nova aba.");
            setTimeout(() => {
                if (!pdfPremiumEmAndamento) setStatusGeracaoPDF("");
            }, 4000);

        } catch (err) {
            console.error("Erro ao gerar/upload do PDF:", err);
            setStatusGeracaoPDF("Falha ao gerar PDF.");
            alert(err.message || "Não foi possível gerar e enviar o relatório.");
        } finally {
            pdfPremiumEmAndamento = false;
            setEstadoBotaoPDF(false);
        }
    }


    document.getElementById('listaPrev')?.addEventListener('click', async () => {
        page -= 1;
        await carregarLista();
    });

    document.getElementById('listaNext')?.addEventListener('click', async () => {
        page += 1;
        await carregarLista();
    });

    document.getElementById('listaPageSize')?.addEventListener('change', async (e) => {
        pageSize = Number(e.target.value) || 50;
        page = 1;
        await carregarLista();
    });

    document.getElementById('topPeriodo')?.addEventListener('change', async () => {
        await carregarTop10();
    });

    (function init() {
        const d = document.getElementById('relData');
        if (d) d.value = hojeISO();

        const m = document.getElementById('relMes');
        if (m) m.value = mesISO();

        atualizarRelatorio();
        atualizarMensal();
    })();
</script>
