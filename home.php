<?php include('template_start.php'); ?>

<style>
    .month-card-wrapper {
        overflow-x: auto;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
        scroll-snap-type: x mandatory;
        padding: 1rem;
    }
    .month-card {
        display: inline-block;
        width: 80%;
        max-width: 250px;
        min-height: 150px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        margin-right: 1rem;
        padding: 1rem;
        text-align: center;
        scroll-snap-align: center;
        transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .month-card.active {
        transform: scale(1.1);
        border-color: #007bff;
        box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.25);
        background-color: #e2f0ff;
    }
    .month-card:last-child {
        margin-right: 0;
    }
    .month-card h5 {
        font-weight: bold;
    }
</style>

<div class="container bg-light p-4 rounded shadow-sm">
    <h2 class="mb-5 text-primary text-center">💸 Resumo Financeiro</h2>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button class="btn btn-outline-secondary btn-sm" id="prevMonthBtn">←</button>
        <div class="month-card-wrapper d-flex flex-grow-1 mx-2">
            </div>
        <button class="btn btn-outline-secondary btn-sm" id="nextMonthBtn">→</button>
    </div>

    <hr class="my-5">
    <h4 class="mb-3 text-secondary text-center">📊 Balanço Detalhado</h4>
    <ul class="list-unstyled small mb-4 px-4 py-3 border rounded bg-white shadow-sm">
        <li class="d-flex justify-content-between py-1">Salário: <strong id="salario">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Descontos CLT: <strong id="descontosCLT" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Mensalidades Fixas: <strong id="mensalidades" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Gastos do Mês: <strong id="gastos" class="text-danger">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Ganhos do Mês: <strong id="ganhos" class="text-success">R$ 0,00</strong></li>
        <li class="d-flex justify-content-between py-1">Saldo Anterior: <strong id="saldoAnterior">R$ 0,00</strong></li>
        <hr>
        <li class="d-flex justify-content-between py-1 fw-bold">Dinheiro Disponível: <strong id="dinheiroDisponivel">R$ 0,00</strong></li>
    </ul>

    <div class="mt-4 text-center">
        <h4 class="mb-3 text-secondary">🧾 Maiores Gastos do Mês</h4>
        <div class="d-flex justify-content-center">
            <canvas id="graficoGastos" style="max-height: 300px; max-width: 300px;"></canvas>
        </div>
        <p class="text-muted small mt-2" id="mensagem-grafico" style="display:none;">Nenhum gasto registrado neste mês.</p>
    </div>
</div>

<script>
    let todosResumos = [];
    let todosGastos = [];
    let todasMensalidades = [];
    let salarioAtual = 0;
    let mesAtualIndex = 0;
    let graficoInstance; // Variável para armazenar a instância do gráfico

    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];

    function formatarReal(valor) {
        valor = parseFloat(valor);
        if (isNaN(valor)) {
            return 'R$ 0,00';
        }
        return 'R$ ' + valor.toFixed(2).replace('.', ',');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return 'Data Inválida';
        return d.toLocaleDateString('pt-BR');
    }

    // Função para atualizar o resumo financeiro detalhado
    function atualizarDetalhes(resumo) {
        if (!resumo) {
            Swal.fire('Aviso', 'Nenhum resumo disponível para este mês.', 'info');
            return;
        }
        $('#salario').text(formatarReal(resumo.salario));
        $('#descontosCLT').text(formatarReal(resumo.descontos_clt));
        $('#mensalidades').text(formatarReal(resumo.total_mensalidades));
        $('#gastos').text(formatarReal(resumo.total_gastos));
        $('#ganhos').text(formatarReal(resumo.total_ganhos));
        $('#saldoAnterior').text(formatarReal(resumo.saldo_anterior));
        $('#dinheiroDisponivel').text(formatarReal(resumo.dinheiro_disponivel));
    }

    // NOVA FUNÇÃO: Cria ou atualiza o gráfico de gastos
    function criarGraficoGastos(ano, mes) {
        const gastosDoMes = todosGastos.filter(g => {
            const dataGasto = new Date(g.data);
            return dataGasto.getFullYear() === ano && (dataGasto.getMonth() + 1) === mes;
        }).sort((a, b) => b.valor - a.valor);

        const canvas = document.getElementById('graficoGastos');
        const mensagem = document.getElementById('mensagem-grafico');

        if (gastosDoMes.length === 0) {
            if (graficoInstance) {
                graficoInstance.destroy();
            }
            canvas.style.display = 'none';
            mensagem.style.display = 'block';
            return;
        }
        
        canvas.style.display = 'block';
        mensagem.style.display = 'none';

        const labels = gastosDoMes.map(g => g.descricao);
        const data = gastosDoMes.map(g => parseFloat(g.valor));
        const totalGastos = data.reduce((sum, value) => sum + value, 0);

        // Gera uma paleta de cores para todos os gastos
        const backgroundColors = gastosDoMes.map(() => {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            return `rgba(${r}, ${g}, ${b}, 0.7)`;
        });

        if (graficoInstance) {
            graficoInstance.destroy(); // Destrói a instância anterior do gráfico
        }

        const ctx = canvas.getContext('2d');
        graficoInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                let label = tooltipItem.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const valor = tooltipItem.raw;
                                const porcentagem = ((valor / totalGastos) * 100).toFixed(2);
                                label += formatarReal(valor) + ' (' + porcentagem + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Funções de navegação do carrossel
    function scrollToActiveCard() {
        const container = $('.month-card-wrapper');
        const activeCard = container.find('.month-card.active');
        if (activeCard.length) {
            container.scrollLeft(activeCard.position().left + container.scrollLeft() - (container.width() / 2) + (activeCard.width() / 2));
        }
    }

    function mudarMes(direcao) {
        if (direcao > 0 && mesAtualIndex < todosResumos.length - 1) {
            mesAtualIndex++;
        } else if (direcao < 0 && mesAtualIndex > 0) {
            mesAtualIndex--;
        }
        renderizarCardsEMudarDetalhes(mesAtualIndex);
    }

    function renderizarCardsEMudarDetalhes(index) {
        const container = $('.month-card-wrapper');
        container.empty();
        
        todosResumos.forEach((resumo, i) => {
            const isActive = i === index;
            const isFuture = resumo.tipo === 'futuro';
            const cardClass = isActive ? 'month-card active' : 'month-card';
            
            const cardHtml = `
                <div class="${cardClass}" data-index="${i}">
                    <h5>${meses[resumo.mes - 1]} ${resumo.ano}</h5>
                    <p class="mb-0 text-muted small">${isFuture ? 'Projeção' : 'Histórico'}</p>
                    <p class="mb-0">${formatarReal(resumo.dinheiro_disponivel)}</p>
                </div>
            `;
            container.append(cardHtml);
        });
        
        atualizarDetalhes(todosResumos[index]);
        criarGraficoGastos(todosResumos[index].ano, todosResumos[index].mes);
        scrollToActiveCard();
    }
    
    function calcularMensalidadesAtivas(todasMensalidades, ano, mes) {
        let total = 0;
        const dataReferencia = new Date(ano, mes - 1, 1);

        todasMensalidades.forEach(m => {
            const inicio = new Date(m.inicio);
            let fim = new Date(inicio);
            
            if (m.duracao > 0) {
                fim.setMonth(inicio.getMonth() + parseInt(m.duracao));
                if (fim.getDate() !== inicio.getDate()) {
                    fim.setDate(0);
                }
            } else {
                fim.setFullYear(inicio.getFullYear() + 100); 
            }

            if (dataReferencia >= inicio && dataReferencia <= fim) {
                total += parseFloat(m.valor);
            }
        });
        return total;
    }

    function carregarDados() {
        $.ajax({
            url: 'home_controller.php',
            method: 'GET',
            data: { funcao: 'todos_resumos' },
            dataType: 'json',
            success: function(responseResumos) {
                if (responseResumos.sucesso) {
                    todosResumos = responseResumos.resumos.map(r => ({...r, tipo: 'historico'}));

                    $.ajax({
                        url: 'ganhos/ganhos_controller.php',
                        method: 'GET',
                        data: { funcao: 'listar' },
                        dataType: 'json',
                        success: function(responseGanhos) {
                            if (responseGanhos.sucesso) {
                                todosGanhos = responseGanhos.dados;
                            } else {
                                console.error('Erro ao buscar ganhos:', responseGanhos.mensagem);
                                todosGanhos = [];
                            }

                            $.ajax({
                                url: 'gastos/gastos_controller.php',
                                method: 'GET',
                                data: { funcao: 'listar' },
                                dataType: 'json',
                                success: function(responseGastos) {
                                    if (responseGastos.sucesso) {
                                        todosGastos = responseGastos.dados;
                                    } else {
                                        console.error('Erro ao buscar gastos:', responseGastos.mensagem);
                                        todosGastos = [];
                                    }

                                    $.ajax({
                                        url: 'mensalidades/mensalidades_controller.php',
                                        method: 'GET',
                                        data: { funcao: 'listar' },
                                        dataType: 'json',
                                        success: function(responseMensalidades) {
                                            if (responseMensalidades.sucesso) {
                                                todasMensalidades = responseMensalidades.dados;
                                                
                                                let ultimoResumo = todosResumos.length > 0 ? todosResumos[todosResumos.length - 1] : null;
                                                
                                                const hoje = new Date();
                                                const anoAtual = hoje.getFullYear();
                                                const mesAtual = hoje.getMonth() + 1;
                                                
                                                let ultimoMesObj;
                                                let saldoAnterior;
                                                
                                                if(todosResumos.length > 0) {
                                                    ultimoResumo = todosResumos[todosResumos.length - 1];
                                                    ultimoMesObj = new Date(ultimoResumo.ano, ultimoResumo.mes, 1);
                                                    saldoAnterior = parseFloat(ultimoResumo.dinheiro_disponivel);
                                                } else {
                                                    ultimoMesObj = new Date(anoAtual, mesAtual, 1);
                                                    saldoAnterior = 0;
                                                }

                                                let novoResumo;
                                                while (ultimoMesObj.getFullYear() < anoAtual || ultimoMesObj.getMonth() + 1 < mesAtual) {
                                                    ultimoMesObj.setMonth(ultimoMesObj.getMonth() + 1);
                                                    const novoMes = ultimoMesObj.getMonth() + 1;
                                                    const novoAno = ultimoMesObj.getFullYear();
                                                    
                                                    const totalMensalidades = calcularMensalidadesAtivas(todasMensalidades, novoAno, novoMes);
                                                    const totalGastos = todosGastos.filter(g => new Date(g.data).getFullYear() === novoAno && (new Date(g.data).getMonth() + 1) === novoMes).reduce((sum, g) => sum + parseFloat(g.valor), 0);
                                                    const totalGanhos = todosGanhos.filter(g => new Date(g.data).getFullYear() === novoAno && (new Date(g.data).getMonth() + 1) === novoMes).reduce((sum, g) => sum + parseFloat(g.valor), 0);
                                                    const salario = ultimoResumo ? parseFloat(ultimoResumo.salario) : 0;
                                                    const descontosCLT = ultimoResumo ? parseFloat(ultimoResumo.descontos_clt) : 0;

                                                    const novoSaldo = saldoAnterior + salario - descontosCLT - totalMensalidades - totalGastos + totalGanhos;
                                                    
                                                    novoResumo = {
                                                        ano: novoAno,
                                                        mes: novoMes,
                                                        salario: salario,
                                                        descontos_clt: descontosCLT,
                                                        total_mensalidades: totalMensalidades,
                                                        total_gastos: totalGastos,
                                                        total_ganhos: totalGanhos,
                                                        dinheiro_disponivel: novoSaldo,
                                                        saldo_anterior: saldoAnterior,
                                                        tipo: 'historico'
                                                    };
                                                    todosResumos.push(novoResumo);
                                                    saldoAnterior = novoSaldo;
                                                }
                                                
                                                ultimoResumo = todosResumos.length > 0 ? todosResumos[todosResumos.length - 1] : novoResumo;
                                                let ultimoMesObjProj = new Date(ultimoResumo.ano, ultimoResumo.mes - 1, 1);
                                                let ultimoSaldoProj = parseFloat(ultimoResumo.dinheiro_disponivel);
                                                let salarioBase = parseFloat(ultimoResumo.salario);
                                                let descontosCLTBase = parseFloat(ultimoResumo.descontos_clt);
                                                
                                                for (let i = 0; i < 12; i++) {
                                                    ultimoMesObjProj.setMonth(ultimoMesObjProj.getMonth() + 1);
                                                    const anoFuturo = ultimoMesObjProj.getFullYear();
                                                    const mesFuturo = ultimoMesObjProj.getMonth() + 1;
                                                    
                                                    const mensalidadesAtivasFuturo = calcularMensalidadesAtivas(todasMensalidades, anoFuturo, mesFuturo);
                                                    
                                                    let ganhosProjetados = todosGanhos.filter(g => new Date(g.data).getFullYear() === anoFuturo && (new Date(g.data).getMonth() + 1) === mesFuturo).reduce((sum, g) => sum + parseFloat(g.valor), 0);
                                                    let gastosProjetados = todosGastos.filter(g => new Date(g.data).getFullYear() === anoFuturo && (new Date(g.data).getMonth() + 1) === mesFuturo).reduce((sum, g) => sum + parseFloat(g.valor), 0);
                                                    
                                                    const novoSaldoProj = ultimoSaldoProj + salarioBase - descontosCLTBase - mensalidadesAtivasFuturo - gastosProjetados + ganhosProjetados;
                                                    
                                                    todosResumos.push({
                                                        ano: anoFuturo,
                                                        mes: mesFuturo,
                                                        salario: salarioBase,
                                                        descontos_clt: descontosCLTBase,
                                                        total_mensalidades: mensalidadesAtivasFuturo,
                                                        total_gastos: gastosProjetados,
                                                        total_ganhos: ganhosProjetados,
                                                        dinheiro_disponivel: novoSaldoProj,
                                                        saldo_anterior: ultimoSaldoProj,
                                                        tipo: 'futuro'
                                                    });
                                                    ultimoSaldoProj = novoSaldoProj;
                                                }
                                                
                                                const hojeIndex = todosResumos.findIndex(r => r.ano === anoAtual && r.mes === mesAtual);
                                                mesAtualIndex = hojeIndex !== -1 ? hojeIndex : 0;
                                                
                                                renderizarCardsEMudarDetalhes(mesAtualIndex);
                                            } else {
                                                console.error('Erro ao buscar mensalidades:', responseMensalidades.mensagem);
                                            }
                                        },
                                        error: () => Swal.fire('Erro', 'Erro de conexão ao carregar mensalidades.', 'error')
                                    });
                                },
                                error: () => Swal.fire('Erro', 'Erro de conexão ao carregar gastos.', 'error')
                            });
                        },
                        error: () => Swal.fire('Erro', 'Erro de conexão ao carregar ganhos.', 'error')
                    });
                } else {
                    Swal.fire('Erro', responseResumos.mensagem, 'error');
                }
            },
            error: () => Swal.fire('Erro', 'Erro de conexão ao carregar resumos.', 'error')
        });
    }
    
    // Event listeners
    $('#prevMonthBtn').on('click', () => mudarMes(-1));
    $('#nextMonthBtn').on('click', () => mudarMes(1));
    $('.month-card-wrapper').on('click', '.month-card', function() {
        mesAtualIndex = $(this).data('index');
        renderizarCardsEMudarDetalhes(mesAtualIndex);
    });

    $(document).ready(function() {
        carregarDados();
    });
</script>

<?php include('template_end.php'); ?>