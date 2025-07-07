<?php
// index.php
// Inclui o template de cabeçalho (que já tem <html>, <head>, <body>, CSS, jQuery e SweetAlert2)
require_once('template_start.php'); 
?>

<div class="container bg-light p-4 rounded shadow-sm">
    <h2 class="mb-5 text-primary text-center">💸 Resumo Financeiro</h2>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">💵 Dinheiro Disponível</h5>
                    <h3 id="saldoDisponivel" class="fw-bold">R$ <span class="valor">0,00</span></h3>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleVisibilidade('saldoDisponivel')">👁️ Alternar</button>
                    <button id="btnAdicionarGastos" class="btn btn-sm btn-primary">+ Adicionar Gastos</button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">🏦 Dinheiro Investido</h5>
                    <h3 id="saldoGuardado" class="fw-bold">R$ <span class="valor">0,00</span></h3>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleVisibilidade('saldoGuardado')">👁️ Alternar</button>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-5"> <h4 class="mb-3 text-secondary text-center">📊 Balanço Detalhado</h4> 
    <ul class="list-unstyled small mb-4 px-4 py-3 border rounded bg-white shadow-sm">
        <li class="d-flex justify-content-between py-1">Salário: <strong id="salario">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Descontos CLT: <strong id="descontosCLT" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Mensalidades Fixas: <strong id="mensalidades" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Gastos do Mês: <strong id="gastos" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Ganhos do Mês: <strong id="ganhos" class="text-success">R$ 0,00</strong></li>
    </ul>

    <div class="mt-4">
        <h4 class="mb-3 text-secondary text-center">🧾 Maiores Gastos do Mês</h4>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-outline-secondary btn-sm" onclick="mudarMes(-1)">← Mês Anterior</button>
            <strong id="mesReferencia"></strong>
            <button class="btn btn-outline-secondary btn-sm" onclick="mudarMes(1)">Próximo Mês →</button>
        </div>

        <table class="table table-striped table-hover shadow-sm" id="tabelaGastosMes">
            <thead class="table-dark">
                <tr>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Data</th>
                    <th class="text-end">Ações</th> </tr>
            </thead>
            <tbody id="tabelaGastosMesBody"> </tbody>
        </table>
    </div>
</div>

<script>
    let dataAtual = new Date();

    function formatarReal(valor) {
        valor = parseFloat(valor);
        if (isNaN(valor)) {
            return 'R$ 0,00';
        }
        return 'R$ ' + valor.toFixed(2).replace('.', ',');
    }

    // Função para alternar visibilidade dos saldos
    function toggleVisibilidade(id) {
        const el = document.querySelector(`#${id} .valor`);
        if (el.textContent.includes('•')) {
            el.textContent = el.dataset.valorOriginal;
        } else {
            el.dataset.valorOriginal = el.textContent;
            el.textContent = '••••••';
        }
    }

    // Função para atualizar o resumo financeiro (Dinheiro Disponível, Salário, etc.)
    function atualizarResumo() {
        // Agora o index_controller.php retorna um objeto com 'sucesso', 'mensagem' e 'dados'
        $.getJSON('index_controller.php', function(response) {
            if (response.sucesso) {
                const data = response.dados; // Acesse os dados do resumo dentro da chave 'dados'
                $('#saldoDisponivel .valor').text(formatarReal(data.dinheiroDisponivel));
                $('#salario').text(formatarReal(data.salario));
                $('#descontosCLT').text(formatarReal(data.descontosCLT));
                $('#mensalidades').text(formatarReal(data.totalMensalidades));
                $('#gastos').text(formatarReal(data.totalGastos));
                $('#ganhos').text(formatarReal(data.totalGanhos));
                // Opcional: Se 'saldoGuardado' vier do backend, atualize aqui
                // $('#saldoGuardado .valor').text(formatarReal(data.saldoInvestido));
            } else {
                Swal.fire('Erro', response.mensagem, 'error');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Erro na requisição AJAX para resumo:", textStatus, errorThrown, jqXHR);
            Swal.fire('Erro', 'Erro de conexão ao carregar resumo financeiro.', 'error');
        });
    }

    // Função para atualizar a tabela dos maiores gastos do mês
    function atualizarGastosMes() {
        const ano = dataAtual.getFullYear();
        const mes = String(dataAtual.getMonth() + 1).padStart(2, '0'); // Mês de 1 a 12

        $('#mesReferencia').text(dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }));

        // A requisição para 'gastos_mes' também vai para index_controller.php
        $.ajax({
            url: 'index_controller.php', // O controller que lida com 'gastos_mes' é o index_controller.php
            method: 'GET',
            data: { funcao: 'gastos_mes', ano, mes },
            dataType: 'json',
            success: function(gastos) { // O index_controller.php para 'gastos_mes' retorna diretamente o array de gastos
                let html = '';

                if (gastos.length === 0) {
                    html = '<tr><td colspan="4" class="text-muted text-center">Nenhum dos 5 maiores gastos registrados neste mês.</td></tr>';
                } else {
                    gastos.forEach(gasto => {
                        html += `
                            <tr>
                                <td>${gasto.descricao}</td>
                                <td>${formatarReal(parseFloat(gasto.valor))}</td>
                                <td>${gasto.data}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editarGasto(${gasto.id})" title="Editar">✏️</button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="excluirGasto(${gasto.id})" title="Excluir">🗑️</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#tabelaGastosMesBody').html(html); // Use o ID correto do tbody
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na requisição AJAX para 'gastos_mes':", textStatus, errorThrown, jqXHR);
                Swal.fire('Erro', 'Erro de conexão ao carregar maiores gastos do mês.', 'error');
            }
        });
    }

    // Função para excluir gasto (chama o gastos_controller.php)
    function excluirGasto(id) {
        Swal.fire({
            title: 'Excluir gasto?',
            text: 'Essa ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Requisição vai para o gastos_controller.php, não index_controller.php
                $.post('gastos/gastos_controller.php', { funcao: 'deletar', id }, function (res) {
                    if (res.sucesso) {
                        Swal.fire('Excluído', res.mensagem, 'success');
                        atualizarResumo();     // Atualiza o resumo financeiro
                        atualizarGastosMes();  // Atualiza a lista dos maiores gastos
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Erro na requisição AJAX para 'deletar' gasto:", textStatus, errorThrown, jqXHR);
                    Swal.fire('Erro', 'Erro de conexão ao excluir gasto.', 'error');
                });
            }
        });
    }

    // Função para editar gasto (redireciona para a página de edição de gastos)
    function editarGasto(id) {
        window.location.href = `gastos/gastos.php?id=${id}`;
    }

    // Função para mudar o mês de referência
    function mudarMes(direcao) {
        dataAtual.setMonth(dataAtual.getMonth() + direcao);
        atualizarGastosMes();
    }

    // Ao carregar a página
    $(document).ready(function() {
        // Atualiza os dados do resumo e os maiores gastos do mês
        atualizarResumo();
        atualizarGastosMes();
        
        // Oculta o saldo guardado por padrão
        toggleVisibilidade('saldoGuardado');

        // Redireciona para a página de adicionar gastos
        $('#btnAdicionarGastos').click(() => {
            window.location.href = 'gastos/gastos.php';
        });
    });
</script>

<?php
// Fecha a div.content e as tags <body> e <html> abertas em template_start.php
// IMPORTANTE: Remova quaisquer includes duplicados de scripts aqui.
?>
    </div></body>
</html>