<?php include('../template_start.php'); ?>

<div class="container bg-light p-4 rounded shadow-sm">
    <h2 class="mb-4">Meu Perfil</h2>

    <form id="formPerfil" class="row g-3">
        <div class="col-md-6">
            <label for="nome" class="form-label">Nome completo</label>
            <input type="text" class="form-control" id="nome" name="nome" required>
        </div>

        <div class="col-md-6">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="col-md-4">
            <label for="nascimento" class="form-label">Data de Nascimento</label>
            <input type="date" class="form-control" id="nascimento" name="nascimento">
        </div>

        <div class="col-md-4">
            <label for="salario" class="form-label">Salário Mensal (R$)</label>
            <input type="number" class="form-control" id="salario" name="salario" step="0.01" required>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function () {
    // Carregar dados
    $.getJSON('perfil_controller.php', { funcao: 'carregar' }, function (res) {
        if (res.sucesso && res.dados) {
            $('#nome').val(res.dados.nome);
            $('#email').val(res.dados.email);
            $('#nascimento').val(res.dados.nascimento);
            $('#salario').val(res.dados.salario);
        }
    });

    // Submeter formulário
    $('#formPerfil').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'perfil_controller.php',
            method: 'POST',
            data: $(this).serialize() + '&funcao=salvar',
            dataType: 'json',
            success: function (res) {
                if (res.sucesso) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso',
                        text: res.mensagem || 'Perfil salvo com sucesso!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: res.mensagem || 'Erro ao salvar perfil.'
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro de conexão',
                    text: 'Não foi possível se comunicar com o servidor.'
                });
            }
        });
    });
});
</script>
