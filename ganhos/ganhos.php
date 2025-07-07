<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm mt-4 mb-4">
    <h2 class="mb-4">Controle de Ganhos</h2>

    <form id="formGanhos" class="row g-3 mb-4">
        <input type="hidden" name="id" id="ganhoId" value="">

        <div class="col-md-4 col-sm-12">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" id="descricao" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12">
            <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12">
            <label class="form-label">Data</label>
            <input type="date" name="data" id="data" class="form-control" required>
        </div>

        <div class="col-md-2 col-sm-12 d-grid">
            <button type="submit" class="btn btn-success" id="btnSalvar">Adicionar</button>
        </div>
    </form>

    <h4 class="mb-3">📈 Ganhos do Mês</h4>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3">
        <button class="btn btn-outline-secondary btn-sm mb-2 mb-md-0" onclick="mudarMes(-1)">← Mês Anterior</button>
        <strong id="mesReferencia"></strong>
        <button class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" onclick="mudarMes(1)">Próximo Mês →</button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="tabelaGanhosMes">
            <thead class="table-success">
                <tr>
                    <th>Descrição</th>
                    <th class="d-none d-sm-table-cell">Valor (R$)</th> <!-- Oculta em telas extra-pequenas, visível a partir de sm -->
                    <th class="d-none d-md-table-cell">Data</th> <!-- Oculta em telas pequenas e extra-pequenas, visível a partir de md -->
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal de Detalhes do Ganho -->
<div class="modal fade" id="modalDetalhesGanho" tabindex="-1" aria-labelledby="modalDetalhesGanhoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesGanhoLabel">Detalhes do Ganho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Descrição:</strong> <span id="modalGanhoDescricao"></span></p>
                <p><strong>Valor:</strong> <span id="modalGanhoValor"></span></p>
                <p><strong>Data:</strong> <span id="modalGanhoData"></span></p>
                <hr>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="modalBtnEditarGanho">Editar Ganho</button> <!-- Botão de editar no modal -->
                    <button type="button" class="btn btn-danger" id="modalBtnExcluirGanho">Excluir Ganho</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
let dataAtual = new Date();

function formatarReal(valor) {
    valor = parseFloat(valor);
    return 'R$ ' + (isNaN(valor) ? '0,00' : valor.toFixed(2).replace('.', ','));
}

function editarGanho(id) {
    $.getJSON('ganhos_controller.php', { funcao: 'buscar', id: id }, function(res) {
        if (res.sucesso) {
            const ganho = res.dados;
            $('#ganhoId').val(ganho.id);
            $('#descricao').val(ganho.descricao);
            $('#valor').val(ganho.valor);
            $('#data').val(ganho.data);
            $('#btnSalvar').text('Atualizar');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            Swal.fire('Erro', res.mensagem, 'error');
        }
    }).fail(() => Swal.fire('Erro', 'Erro ao buscar ganho.', 'error'));
}

function excluirGanho(id) {
    Swal.fire({
        title: 'Excluir ganho?',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ganhos_controller.php', { funcao: 'excluir', id }, function(res) {
                if (res.sucesso) {
                    Swal.fire('Excluído', res.mensagem, 'success');
                    atualizarGanhosMes();
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            }, 'json').fail(() => Swal.fire('Erro', 'Erro ao excluir ganho.', 'error'));
        }
    });
}

$('#formGanhos').on('submit', function(e) {
    e.preventDefault();

    let funcao = $('#ganhoId').val() ? 'atualizar' : 'salvar';

    $.ajax({
        url: 'ganhos_controller.php',
        method: 'POST',
        data: $(this).serialize() + '&funcao=' + funcao,
        dataType: 'json',
        success: function(res) {
            if (res.sucesso) {
                Swal.fire('Sucesso', res.mensagem, 'success');
                $('#formGanhos')[0].reset();
                $('#ganhoId').val('');
                $('#btnSalvar').text('Adicionar');
                atualizarGanhosMes();
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        },
        error: () => Swal.fire('Erro', 'Erro de conexão.', 'error')
    });
});

function atualizarGanhosMes() {
    const ano = dataAtual.getFullYear();
    const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
    $('#mesReferencia').text(dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }));

    $.getJSON('ganhos_controller.php', { funcao: 'ganhos_mes', ano, mes }, function(ganhos) {
        let html = '';

        if (ganhos.length === 0) {
            html = '<tr><td colspan="4" class="text-muted text-center">Nenhum ganho registrado neste mês.</td></tr>';
        } else {
            ganhos.forEach(ganho => {
                html += `
                    <tr data-id="${ganho.id}"
                        data-descricao="${ganho.descricao}"
                        data-valor="${ganho.valor}"
                        data-data="${ganho.data}">
                        <td>${ganho.descricao}</td>
                        <td class="d-none d-sm-table-cell">${formatarReal(parseFloat(ganho.valor))}</td>
                        <td class="d-none d-md-table-cell">${ganho.data}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-info btn-detalhes-ganho d-block d-md-none mb-1">Detalhes</button> <!-- Botão visível só em telas pequenas -->
                            <button class="btn btn-sm btn-outline-primary me-1 d-none d-md-inline-block" onclick="editarGanho(${ganho.id})">✏️</button> <!-- Botão visível só em telas médias+ -->
                            <button class="btn btn-sm btn-outline-danger d-none d-md-inline-block" onclick="excluirGanho(${ganho.id})">🗑️</button> <!-- Botão visível só em telas médias+ -->
                        </td>
                    </tr>
                `;
            });
        }

        $('#tabelaGanhosMes tbody').html(html);
    }).fail(() => Swal.fire('Erro', 'Erro ao carregar ganhos do mês.', 'error'));
}

function mudarMes(direcao) {
    dataAtual.setMonth(dataAtual.getMonth() + direcao);
    atualizarGanhosMes();
}

// Event listener para abrir o modal de detalhes do ganho
$('#tabelaGanhosMes tbody').on('click', '.btn-detalhes-ganho', function() {
    const rowData = $(this).closest('tr').data(); // Pega todos os data-atributos da linha
    
    $('#modalGanhoDescricao').text(rowData.descricao);
    $('#modalGanhoValor').text(formatarReal(rowData.valor));
    $('#modalGanhoData').text(rowData.data);
    
    // Atribui o ID aos botões de ação do modal
    $('#modalBtnEditarGanho').data('id', rowData.id); // Atribui ID ao botão de editar
    $('#modalBtnExcluirGanho').data('id', rowData.id);

    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesGanho'));
    modal.show();
});

// Event listener para o botão de edição dentro do modal
$('#modalBtnEditarGanho').on('click', function() {
    const id = $(this).data('id');
    editarGanho(id); // Chama a função de edição existente
    // Fechar o modal após iniciar a edição
    const modalElement = document.getElementById('modalDetalhesGanho');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) {
        modal.hide();
    }
});

// Event listener para o botão de exclusão dentro do modal
$('#modalBtnExcluirGanho').on('click', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'Excluir ganho?',
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            excluirGanho(id);
            // Fechar o modal após a confirmação de exclusão
            const modalElement = document.getElementById('modalDetalhesGanho');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
    });
});

$(document).ready(() => {
    atualizarGanhosMes();

    const today = new Date();
    $('#data').val(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`);

    const ganhoIdParam = new URLSearchParams(window.location.search).get('id');
    if (ganhoIdParam) editarGanho(parseInt(ganhoIdParam));
});
</script>
