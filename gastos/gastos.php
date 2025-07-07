<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm mt-4 mb-4"> <h2 class="mb-4">Controle de Gastos</h2>

    <form id="formGastos" class="row g-3 mb-4">
        <input type="hidden" name="id" id="gastoId" value="">

        <div class="col-md-4 col-sm-12"> <label class="form-label">Descrição</label>
            <input type="text" name="descricao" id="descricao" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12"> <label class="form-label">Valor (R$)</label>
            <input type="number" step="0.01" name="valor" id="valor" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12"> <label class="form-label">Data</label>
            <input type="date" name="data" id="data" class="form-control" required>
        </div>

        <div class="col-md-2 col-sm-12 d-grid"> <button type="submit" class="btn btn-primary" id="btnSalvar">Adicionar</button>
        </div>
    </form>

    <h4 class="mb-3">🗓 Gastos do Mês</h4>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3"> <button class="btn btn-outline-secondary btn-sm mb-2 mb-md-0" onclick="mudarMes(-1)">← Mês Anterior</button> <strong id="mesReferencia"></strong>
        <button class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" onclick="mudarMes(1)">Próximo Mês →</button> </div>

    <div class="table-responsive"> <table class="table table-striped table-hover" id="tabelaGastosMes">
            <thead class="table-dark">
                <tr>
                    <th>Descrição</th>
                    <th>Valor (R$)</th>
                    <th>Data</th>
                    <th class="text-end" style="padding-right: 30px;">Ações</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
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

    function editarGasto(id) {
        console.log("Tentando editar gasto com ID:", id); // PONTO DE DEPURACAO

        $.getJSON('gastos_controller.php', { funcao: 'buscar', id: id }, function(res) {
            console.log("Resposta do servidor para 'buscar' gasto:", res); // PONTO DE DEPURACAO

            if(res.sucesso) {
                const gasto = res.dados; // Seu controller retorna 'dados' para o objeto encontrado
                $('#gastoId').val(gasto.id);
                $('#descricao').val(gasto.descricao);
                $('#valor').val(gasto.valor);
                $('#data').val(gasto.data);
                $('#btnSalvar').text('Atualizar');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                Swal.fire('Erro', res.mensagem, 'error');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Erro na requisição AJAX para 'buscar' gasto:", textStatus, errorThrown, jqXHR); // PONTO DE DEPURACAO
            Swal.fire('Erro', 'Erro de conexão ao buscar gasto.', 'error');
        });
    }

    function excluirGasto(id) {
        Swal.fire({
            title: 'Excluir gasto?',
            text: "Esta ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('gastos_controller.php', { funcao: 'deletar', id: id }, function(res) {
                    if(res.sucesso) {
                        Swal.fire('Excluído', res.mensagem, 'success');
                        atualizarGastosMes(); // Atualiza apenas a tabela mensal
                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }
                }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Erro na requisição AJAX para 'deletar' gasto:", textStatus, errorThrown, jqXHR); // PONTO DE DEPURACAO
                    Swal.fire('Erro', 'Erro de conexão ao excluir gasto.', 'error');
                });
            }
        });
    }

    $('#formGastos').on('submit', function(e) {
        e.preventDefault();

        let funcao = $('#gastoId').val() ? 'atualizar' : 'criar';

        $.ajax({
            url: 'gastos_controller.php',
            method: 'POST',
            data: $(this).serialize() + '&funcao=' + funcao,
            dataType: 'json',
            success: function(res) {
                if(res.sucesso) {
                    Swal.fire('Sucesso', res.mensagem, 'success');
                    $('#formGastos')[0].reset();
                    $('#gastoId').val('');
                    $('#btnSalvar').text('Adicionar');
                    atualizarGastosMes(); // Atualiza apenas a tabela mensal
                } else {
                    Swal.fire('Erro', res.mensagem, 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na requisição AJAX para 'criar/atualizar' gasto:", textStatus, errorThrown, jqXHR); // PONTO DE DEPURACAO
                Swal.fire('Erro', 'Erro de conexão.', 'error');
            }
        });
    });

    function atualizarGastosMes() {
        const ano = dataAtual.getFullYear();
        const mes = String(dataAtual.getMonth() + 1).padStart(2, '0'); // Mês de 1 a 12

        $('#mesReferencia').text(dataAtual.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }));

        $.ajax({
            url: 'gastos_controller.php',
            method: 'GET',
            data: { funcao: 'gastos_mes', ano, mes },
            dataType: 'json',
            success: function(res) {
                let html = '';
                const gastos = res; // Como gastosMes no controller retorna apenas o array de dados, 'res' aqui já é o array de gastos.

                if(gastos.length === 0) {
                    html = '<tr><td colspan="4" class="text-muted text-center">Nenhum gasto registrado neste mês.</td></tr>';
                } else {
                    gastos.forEach(gasto => {
                        html += `
                            <tr>
                                <td>${gasto.descricao}</td>
                                <td>${formatarReal(parseFloat(gasto.valor))}</td>
                                <td>${gasto.data}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editarGasto(${gasto.id})">✏️</button>
                                    <button class="btn btn-sm btn-outline-danger me-1" onclick="excluirGasto(${gasto.id})">🗑️</button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#tabelaGastosMes tbody').html(html); // Adicionado tbody aqui para ser mais específico
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Erro na requisição AJAX para 'gastos_mes':", textStatus, errorThrown, jqXHR); // PONTO DE DEPURACAO
                Swal.fire('Erro', 'Erro de conexão ao carregar gastos do mês.', 'error');
            }
        });
    }

    function mudarMes(direcao) {
        dataAtual.setMonth(dataAtual.getMonth() + direcao);
        atualizarGastosMes();
    }

    $(document).ready(() => {
        atualizarGastosMes(); // Garante que a tabela mensal seja carregada ao iniciar.

        const urlParams = new URLSearchParams(window.location.search);
        const gastoIdParam = urlParams.get('id');

        if (gastoIdParam) {
            editarGasto(parseInt(gastoIdParam)); // Preenche o formulário se ID estiver na URL (modo edição)
        } else {
            // Define a data atual como padrão para um novo gasto
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            $('#data').val(`${year}-${month}-${day}`);
        }
    });
</script>