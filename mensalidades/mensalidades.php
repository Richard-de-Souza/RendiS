<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm mt-4 mb-4">
    <h2 class="mb-4">Mensalidades Fixas</h2>

    <form id="formMensalidade" class="row g-3 mb-4">
        <div class="col-md-4 col-sm-12">
            <label class="form-label">Descrição</label>
            <input type="text" name="descricao" class="form-control" required>
        </div>

        <div class="col-md-2 col-sm-12">
            <label class="form-label">Valor (R$)</label>
            <input type="number" name="valor" step="0.01" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12">
            <label class="form-label">Início</label>
            <input type="date" name="inicio" class="form-control" required>
        </div>

        <div class="col-md-3 col-sm-12">
            <label class="form-label">Duração (meses)</label>
            <input type="number" name="duracao" class="form-control" required>
        </div>

        <div class="col-12 d-grid">
            <button type="submit" class="btn btn-primary">Adicionar Mensalidade</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="tabelaMensalidades">
            <thead class="table-dark">
                <tr>
                    <th>Descrição</th>
                    <th class="d-none d-sm-table-cell">Valor</th> <th class="d-none d-md-table-cell">Início</th> <th class="d-none d-lg-table-cell">Duração</th> <th class="d-none d-lg-table-cell">Fim Estimado</th> <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalDetalhesMensalidade" tabindex="-1" aria-labelledby="modalDetalhesMensalidadeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesMensalidadeLabel">Detalhes da Mensalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Descrição:</strong> <span id="modalDescricao"></span></p>
                <p><strong>Valor:</strong> <span id="modalValor"></span></p>
                <p><strong>Início:</strong> <span id="modalInicio"></span></p>
                <p><strong>Duração:</strong> <span id="modalDuracao"></span></p>
                <p><strong>Fim Estimado:</strong> <span id="modalFimEstimado"></span></p>
                <hr>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" id="modalBtnExcluir">Excluir Mensalidade</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function formatDate(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) {
            return 'Data Inválida';
        }
        return d.toLocaleDateString('pt-BR');
    }

    function formatCurrency(value) {
        const parsedValue = parseFloat(value);
        if (isNaN(parsedValue)) {
            return 'R$ 0,00';
        }
        return 'R$ ' + parsedValue.toFixed(2).replace('.', ',');
    }

    function calculaFim(inicio, duracao) {
        let date = new Date(inicio + 'T00:00:00');
        const parsedDuracao = parseInt(duracao);
        
        if (isNaN(date.getTime()) || isNaN(parsedDuracao)) {
            return 'Inválido';
        }

        date.setMonth(date.getMonth() + parsedDuracao);
        const day = new Date(inicio + 'T00:00:00').getDate();
        if (date.getDate() !== day) {
            date.setDate(0); 
        }
        return formatDate(date.toISOString().slice(0, 10));
    }

    function carregarMensalidades() {
        $.ajax({
            url: 'mensalidades_controller.php?funcao=listar',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                const tbody = $('#tabelaMensalidades tbody');
                tbody.empty();

                if (data.length > 0) {
                    data.forEach(row => {
                        const fim = calculaFim(row.inicio, row.duracao);
                        const tr = `
                            <tr data-id="${row.id}"
                                data-descricao="${row.descricao}"
                                data-valor="${row.valor}"
                                data-inicio="${row.inicio}"
                                data-duracao="${row.duracao}"
                                data-fim="${fim}">
                                <td>${row.descricao}</td>
                                <td class="d-none d-sm-table-cell">${formatCurrency(row.valor)}</td>
                                <td class="d-none d-md-table-cell">${formatDate(row.inicio)}</td>
                                <td class="d-none d-lg-table-cell">${row.duracao} meses</td>
                                <td class="d-none d-lg-table-cell">${fim}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-info btn-detalhes d-block d-md-none mb-1">Detalhes</button> <button class="btn btn-sm btn-danger btn-excluir-tabela d-none d-md-inline-block" data-id="${row.id}">Excluir</button> </td>
                            </tr>
                        `;
                        tbody.append(tr);
                    });
                } else {
                    tbody.append('<tr><td colspan="6" class="text-center text-muted">Nenhuma mensalidade cadastrada.</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                Swal.fire('Erro', 'Não foi possível carregar as mensalidades: ' + error, 'error');
                console.error("Erro ao carregar mensalidades:", status, error, xhr);
            }
        });
    }

    // Event listener para abrir o modal de detalhes
    $('#tabelaMensalidades tbody').on('click', '.btn-detalhes', function() {
        const rowData = $(this).closest('tr').data(); // Pega todos os data-atributos da linha
        
        $('#modalDescricao').text(rowData.descricao);
        $('#modalValor').text(formatCurrency(rowData.valor));
        $('#modalInicio').text(formatDate(rowData.inicio));
        $('#modalDuracao').text(rowData.duracao + ' meses');
        $('#modalFimEstimado').text(rowData.fim);
        
        // Atribui o ID ao botão de exclusão do modal
        $('#modalBtnExcluir').data('id', rowData.id);

        const modal = new bootstrap.Modal(document.getElementById('modalDetalhesMensalidade'));
        modal.show();
    });

    // Event listener para o botão de exclusão dentro do modal
    $('#modalBtnExcluir').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Deseja realmente excluir esta mensalidade?',
            text: "Essa ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                excluirMensalidade(id);
                // Fechar o modal após a confirmação de exclusão
                const modalElement = document.getElementById('modalDetalhesMensalidade');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            }
        });
    });

    // Event listener para o botão de exclusão da tabela (para desktop)
    $('#tabelaMensalidades tbody').on('click', '.btn-excluir-tabela', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Deseja realmente excluir esta mensalidade?',
            text: "Essa ação não pode ser desfeita!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                excluirMensalidade(id);
            }
        });
    });

    function excluirMensalidade(id) {
        $.ajax({
            url: 'mensalidades_controller.php',
            type: 'POST',
            dataType: 'json',
            data: { funcao: 'deletar', id: id },
            success: function (data) {
                if (data.sucesso) {
                    Swal.fire('Excluído!', data.mensagem, 'success');
                    carregarMensalidades();
                } else {
                    Swal.fire('Erro!', data.mensagem, 'error');
                }
            },
            error: function (xhr, status, error) {
                Swal.fire('Erro', 'Não foi possível excluir a mensalidade: ' + error, 'error');
                console.error("Erro ao excluir mensalidade:", status, error, xhr);
            }
        });
    }

    $('#formMensalidade').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serializeArray();
        formData.push({name: 'funcao', value: 'criar'});

        $.ajax({
            url: 'mensalidades_controller.php',
            type: 'POST',
            dataType: 'json',
            data: $.param(formData),
            success: function (data) {
                if (data.sucesso) {
                    $('#formMensalidade')[0].reset();
                    Swal.fire('Sucesso!', data.mensagem, 'success');
                    carregarMensalidades();
                } else {
                    Swal.fire('Erro!', data.mensagem, 'error');
                }
            },
            error: function (xhr, status, error) {
                Swal.fire('Erro', 'Não foi possível salvar a mensalidade: ' + error, 'error');
                console.error("Erro ao salvar mensalidade:", status, error, xhr);
            }
        });
    });

    $(document).ready(function() {
        carregarMensalidades();
    });
</script>