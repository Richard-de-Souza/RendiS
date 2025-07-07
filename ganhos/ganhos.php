<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm mt-4 mb-4"> <h2 class="mb-4">Controle de Ganhos</h2>

    <form id="formGanhos" class="row g-3 mb-4">
        <input type="hidden" name="id" id="ganhoId" value="">

        <div class="col-md-4 col-sm-12"> <label class="form-label">Descrição</label>
            <input type="text" name="descricao" id="descricao" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12"> <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12"> <label class="form-label">Data</label>
            <input type="date" name="data" id="data" class="form-control" required>
        </div>

        <div class="col-md-2 col-sm-12 d-grid"> <button type="submit" class="btn btn-success" id="btnSalvar">Adicionar</button>
        </div>
    </form>

    <h4 class="mb-3">📈 Ganhos do Mês</h4>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3"> <button class="btn btn-outline-secondary btn-sm mb-2 mb-md-0" onclick="mudarMes(-1)">← Mês Anterior</button> <strong id="mesReferencia"></strong>
        <button class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" onclick="mudarMes(1)">Próximo Mês →</button> </div>

    <div class="table-responsive"> <table class="table table-striped table-hover" id="tabelaGanhosMes">
            <thead class="table-success">
                <tr>
                    <th>Descrição</th>
                    <th>Valor (R$)</th>
                    <th>Data</th>
                    <th class="text-end" style="padding-right: 30px;">Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
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
                    <tr>
                        <td>${ganho.descricao}</td>
                        <td>${formatarReal(ganho.valor)}</td>
                        <td>${ganho.data}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editarGanho(${ganho.id})">✏️</button>
                            <button class="btn btn-sm btn-outline-danger me-1" onclick="excluirGanho(${ganho.id})">🗑️</button>
                        </td>
                    </tr>
                `;
            });
        }

        $('#tabelaGanhosMes tbody').html(html); // Adicionado tbody aqui para ser mais específico
    }).fail(() => Swal.fire('Erro', 'Erro ao carregar ganhos do mês.', 'error'));
}

function mudarMes(direcao) {
    dataAtual.setMonth(dataAtual.getMonth() + direcao);
    atualizarGanhosMes();
}

$(document).ready(() => {
    atualizarGanhosMes();

    const today = new Date();
    $('#data').val(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`);

    const ganhoIdParam = new URLSearchParams(window.location.search).get('id');
    if (ganhoIdParam) editarGanho(parseInt(ganhoIdParam));
});
</script>