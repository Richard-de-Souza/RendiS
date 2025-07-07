<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm mt-4 mb-4">
    <h2 class="mb-4">Meus Investimentos</h2>

    <form id="formInvestimento" class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6 col-12">
            <label class="form-label">Ticker (ex: PETR4)</label>
            <input type="text" name="ticker" class="form-control" required>
        </div>

        <div class="col-md-2 col-sm-6 col-12">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantidade" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-6 col-12">
            <label class="form-label">Preço Médio Pago (R$)</label>
            <input type="number" step="0.01" name="preco_medio" class="form-control" required>
        </div>

        <div class="col-12 col-md-4 d-grid">
            <button type="submit" class="btn btn-primary mt-md-4">Salvar Investimento</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="tabelaInvestimentos">
            <thead class="table-dark">
                <tr>
                    <th>Ticker</th>
                    <th class="d-none d-sm-table-cell">Quantidade</th> <th class="d-none d-md-table-cell">Preço Médio</th> <th class="d-none d-md-table-cell">Preço Atual</th> <th class="d-none d-lg-table-cell">Valorização</th> <th class="d-none d-lg-table-cell">Lucro Total</th> <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalDetalhesInvestimento" tabindex="-1" aria-labelledby="modalDetalhesInvestimentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesInvestimentoLabel">Detalhes do Investimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Ticker:</strong> <span id="modalTicker"></span></p>
                <p><strong>Quantidade:</strong> <span id="modalQuantidade"></span></p>
                <p><strong>Preço Médio:</strong> <span id="modalPrecoMedio"></span></p>
                <p><strong>Preço Atual:</strong> <span id="modalPrecoAtual"></span></p>
                <p><strong>Valorização:</strong> <span id="modalValorizacao"></span></p>
                <p><strong>Lucro Total:</strong> <span id="modalLucroTotal"></span></p>
                <hr>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" id="modalBtnExcluirInvestimento">Excluir Investimento</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function formatCurrency(value) {
        const parsedValue = parseFloat(value);
        if (isNaN(parsedValue)) {
            return 'R$ 0,00';
        }
        return 'R$ ' + parsedValue.toFixed(2).replace('.', ',');
    }

    function carregarInvestimentos() {
        $.getJSON('investimentos_controller.php', { funcao: 'listar' }, function (dados) {
            let html = '';
            if (dados.length === 0) {
                html = '<tr><td colspan="7" class="text-muted text-center">Nenhum investimento registrado.</td></tr>';
            } else {
                dados.forEach(item => {
                    const precoAtual = parseFloat(item.preco_atual) || 0;
                    const quantidade = parseInt(item.quantidade) || 0;
                    const precoMedio = parseFloat(item.preco_medio) || 0;

                    const totalPago = quantidade * precoMedio;
                    const totalAtual = quantidade * precoAtual;
                    const lucro = totalAtual - totalPago;
                    const perc = precoMedio > 0
                        ? (((precoAtual - precoMedio) / precoMedio) * 100).toFixed(2)
                        : '0.00';

                    const precoMedioFormat = precoMedio.toFixed(2).replace('.', ',');
                    const precoAtualFormat = precoAtual.toFixed(2).replace('.', ',');
                    const lucroFormat = lucro.toFixed(2).replace('.', ',');

                    // Determinando as classes de cor para valorização/lucro
                    const percColorClass = perc > 0 ? 'text-success' : (perc < 0 ? 'text-danger' : '');
                    const lucroColorClass = lucro > 0 ? 'text-success' : (lucro < 0 ? 'text-danger' : '');

                    html += `
                        <tr data-id="${item.id}"
                            data-ticker="${item.ticker}"
                            data-quantidade="${quantidade}"
                            data-preco_medio="${precoMedioFormat}"
                            data-preco_atual="${precoAtualFormat}"
                            data-valorizacao="${perc}%"
                            data-lucro_total="R$ ${lucroFormat}"
                            data-perc_color_class="${percColorClass}"
                            data-lucro_color_class="${lucroColorClass}">
                            <td>${item.ticker}</td>
                            <td class="d-none d-sm-table-cell">${quantidade}</td>
                            <td class="d-none d-md-table-cell">R$ ${precoMedioFormat}</td>
                            <td class="d-none d-md-table-cell">R$ ${precoAtualFormat}</td>
                            <td class="d-none d-lg-table-cell ${percColorClass}">${perc}%</td>
                            <td class="d-none d-lg-table-cell ${lucroColorClass}">R$ ${lucroFormat}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info btn-detalhes-investimento d-block d-md-none mb-1">Detalhes</button>
                                <button class="btn btn-sm btn-danger btn-excluir-investimento-tabela d-none d-md-inline-block" data-id="${item.id}">Excluir</button>
                            </td>
                        </tr>
                    `;
                });
            }
            $('#tabelaInvestimentos tbody').html(html);
        }).fail(() => Swal.fire('Erro', 'Erro ao carregar investimentos.', 'error'));
    }

    $('#formInvestimento').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'investimentos_controller.php',
            method: 'POST',
            data: $(this).serialize() + '&funcao=criar',
            dataType: 'json',
            success: function (res) {
                if (res.sucesso) {
                    Swal.fire('Sucesso', res.mensagem, 'success');
                    $('#formInvestimento')[0].reset();
                    carregarInvestimentos();
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            },
            error: () => Swal.fire('Erro', 'Erro de conexão.', 'error')
        });
    });

    // Event listener para abrir o modal de detalhes do investimento
    $('#tabelaInvestimentos tbody').on('click', '.btn-detalhes-investimento', function() {
        const rowData = $(this).closest('tr').data();
        
        $('#modalTicker').text(rowData.ticker);
        $('#modalQuantidade').text(rowData.quantidade);
        $('#modalPrecoMedio').text(`R$ ${rowData.preco_medio}`);
        $('#modalPrecoAtual').text(`R$ ${rowData.preco_atual}`);
        
        $('#modalValorizacao').text(rowData.valorizacao).removeClass().addClass(rowData.perc_color_class);
        $('#modalLucroTotal').text(rowData.lucro_total).removeClass().addClass(rowData.lucro_color_class);
        
        $('#modalBtnExcluirInvestimento').data('id', rowData.id);

        const modal = new bootstrap.Modal(document.getElementById('modalDetalhesInvestimento'));
        modal.show();
    });

    // Event listener para o botão de exclusão dentro do modal
    $('#modalBtnExcluirInvestimento').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Excluir investimento?',
            text: "Essa ação não poderá ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                excluirInvestimento(id);
                const modalElement = document.getElementById('modalDetalhesInvestimento');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });
    });

    // Event listener para o botão de exclusão da tabela (para desktop)
    $('#tabelaInvestimentos tbody').on('click', '.btn-excluir-investimento-tabela', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Excluir investimento?',
            text: "Essa ação não poderá ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                excluirInvestimento(id);
            }
        });
    });

    function excluirInvestimento(id) {
        $.post('investimentos_controller.php', { funcao: 'deletar', id }, function (res) {
            if (res.sucesso) {
                Swal.fire('Excluído!', res.mensagem, 'success');
                carregarInvestimentos();
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }, 'json').fail(() => Swal.fire('Erro', 'Erro ao excluir investimento.', 'error'));
    }

    $(document).ready(() => carregarInvestimentos());
</script>