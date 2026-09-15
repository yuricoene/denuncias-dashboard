(function () {
  const campoBusca = document.getElementById('campoBusca');
  const filtroTipo = document.getElementById('filtroTipo');
  const filtroStatus = document.getElementById('filtroStatus');
  const corpoTabela = document.getElementById('corpoTabela');
  const rodapeBusca = document.getElementById('rodapeBusca');

  // painel público (consultar.php) não tem esses elementos -- nada a fazer
  if (!corpoTabela) return;

  const CORES_STATUS = {
    'Recebida': '#6B7A87',
    'Em análise': '#B8813C',
    'Em investigação': '#24405C',
    'Concluída': '#4B6A53',
    'Arquivada': '#8C6A5A',
  };

  let temporizador = null;

  function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  }

  function renderLinhas(resultados) {
    if (resultados.length === 0) {
      corpoTabela.innerHTML =
        '<tr><td colspan="3" class="busca__vazio">Nenhum protocolo encontrado. Confira o número informado ou ajuste os filtros.</td></tr>';
      return;
    }

    corpoTabela.innerHTML = resultados
      .map((r) => {
        const cor = CORES_STATUS[r.status] || '#999';
        return `<tr>
          <td class="mono"><a href="denuncia.php?id=${encodeURIComponent(r.id)}">${escapeHtml(r.protocolo)}</a></td>
          <td>${escapeHtml(r.tipo)}</td>
          <td><span class="ponto" style="background:${cor};"></span>${escapeHtml(r.status)}</td>
        </tr>`;
      })
      .join('');
  }

  async function buscar() {
    const params = new URLSearchParams({
      q: campoBusca.value.trim(),
      tipo: filtroTipo.value,
      status: filtroStatus.value,
    });

    corpoTabela.setAttribute('aria-busy', 'true');

    try {
      const resposta = await fetch('api.php?' + params.toString());
      if (!resposta.ok) throw new Error('Falha na consulta');
      const dados = await resposta.json();

      renderLinhas(dados.resultados);
      rodapeBusca.textContent =
        dados.exibindo < dados.total
          ? `Exibindo ${dados.exibindo} de ${dados.total} protocolos.`
          : `${dados.total} protocolo${dados.total === 1 ? '' : 's'} encontrado${dados.total === 1 ? '' : 's'}.`;
    } catch (erro) {
      rodapeBusca.textContent = 'Não foi possível carregar os resultados agora.';
    } finally {
      corpoTabela.removeAttribute('aria-busy');
    }
  }

  function buscarComAtraso() {
    clearTimeout(temporizador);
    temporizador = setTimeout(buscar, 250);
  }

  campoBusca.addEventListener('input', buscarComAtraso);
  filtroTipo.addEventListener('change', buscar);
  filtroStatus.addEventListener('change', buscar);

  buscar();
})();
