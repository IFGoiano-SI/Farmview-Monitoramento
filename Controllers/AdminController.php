<?php

namespace Controllers;

use Models\ExperimentoModel;
use Models\UsuarioModel;
use RuntimeException;
use Services\Logger;
use Services\Upload;
use Services\Validacao;

class AdminController extends BaseController
{
    private const POR_PAGINA = 5;

    private UsuarioModel $usuarios;
    private ExperimentoModel $experimentos;

    public function __construct()
    {
        $this->usuarios = new UsuarioModel();
        $this->experimentos = new ExperimentoModel();
    }

    public function index(): void
    {
        $totalRegistros = $this->experimentos->totalComBancada();
        $totalPaginas = max(1, (int) ceil($totalRegistros / self::POR_PAGINA));
        $paginaAtual = min(max(1, (int) ($_GET['pagina'] ?? 1)), $totalPaginas);

        $this->loadView('adm/index', [
            'pagina' => 'Experimento de usuario',
            'quantidadeExperimentos' => $this->experimentos->total(),
            'topExperimentos' => $this->experimentos->usuarioComMaisExperimentos(),
            'totalUsuarios' => $this->usuarios->total(),
            'experimentosPaginados' => $this->experimentos->listarPaginado(self::POR_PAGINA, ($paginaAtual - 1) * self::POR_PAGINA),
            'paginaAtual' => $paginaAtual,
            'totalPaginas' => $totalPaginas,
        ]);
    }

    public function infoUser(string $id): void
    {
        $experimento = $this->experimentos->detalhado($this->idDaUrlOu400($id));

        if (!$experimento) {
            $this->redirect(base_url('adm/index'), 'Experimento não encontrado.', 'danger');
        }

        $this->loadView('adm/infoUser', ['infoUser' => $experimento]);
    }

    /** Reativa um experimento, respeitando a regra de um experimento ativo por usuário. */
    public function ativarExperimentos(): void
    {
        $experimentoId = (int) ($_POST['experimentos_id'] ?? 0);
        $voltar = base_url('adm/infoUser/' . id_url($experimentoId));
        $experimento = $this->experimentos->detalhado($experimentoId);

        if (!$experimento) {
            $this->redirect($voltar, 'Experimento não encontrado.', 'danger');
        }

        if ($experimento->experimentos_status === ExperimentoModel::STATUS_ATIVO) {
            $this->redirect($voltar, 'Este experimento já está ativo.', 'warning');
        }

        // O dono vem do banco, nunca do formulário.
        if ($this->experimentos->contarAtivosDoUsuario((int) $experimento->usuarios_id) >= 1) {
            $this->redirect($voltar, 'O usuário ja possui um experimento ativo!', 'danger');
        }

        $this->experimentos->alterarStatus($experimentoId, ExperimentoModel::STATUS_ATIVO);
        Logger::info('Experimento reativado pelo administrador', ['experimento' => $experimentoId]);

        $this->redirect($voltar, 'Experimento ativado com sucesso!');
    }

    public function editarExperimentos(): void
    {
        $post = $_POST;
        $experimentoId = (int) ($post['experimentos_id'] ?? 0);
        $voltar = base_url('adm/infoUser/' . id_url($experimentoId));
        $experimento = $this->experimentos->detalhado($experimentoId);

        if (!$experimento) {
            $this->redirect($voltar, 'Experimento não encontrado.', 'danger');
        }

        $dadosExperimento = [
            'experimentos_titulo' => trim((string) ($post['experimentos_titulo'] ?? $experimento->experimentos_titulo)),
            'experimentos_inicio' => $post['experimentos_inicio'] ?? $experimento->experimentos_inicio,
            'experimentos_final' => $post['experimentos_final'] ?? $experimento->experimentos_final,
            'experimentos_sala' => trim((string) ($post['experimentos_sala'] ?? $experimento->experimentos_sala)),
        ];

        $dadosBancada = [
            'bancadas_tipo_plantacao' => trim((string) ($post['bancadas_tipo_plantacao'] ?? $experimento->bancadas_tipo_plantacao)),
            'bancadas_nome_cientifico' => trim((string) ($post['bancadas_nome_cientifico'] ?? $experimento->bancadas_nome_cientifico)),
            'bancadas_quantidade' => $post['bancadas_quantidade'] ?? $experimento->bancadas_quantidade,
        ];

        $erro = Validacao::experimento($dadosExperimento) ?? Validacao::bancada($dadosBancada, false);

        if ($erro) {
            $this->redirect($voltar, $erro, 'danger');
        }

        $dadosBancada['bancadas_quantidade'] = (int) $dadosBancada['bancadas_quantidade'];

        $this->experimentos->transacao(function () use ($experimentoId, $dadosExperimento, $dadosBancada) {
            $this->experimentos->atualizarDados($experimentoId, $dadosExperimento);
            $this->experimentos->atualizarBancada($experimentoId, $dadosBancada);
        });

        $this->redirect($voltar, 'Experimento editado com sucesso!');
    }

    public function listarUsuarios(): void
    {
        $this->loadView('adm/usuarios', ['usuarios' => $this->usuarios->listarTodos()]);
    }

    public function createUser(): void
    {
        $post = $_POST;
        $voltar = base_url('adm/usuarios');

        $dados = [
            'usuarios_nome' => trim((string) ($post['usuarios_nome'] ?? '')),
            'usuarios_escolaridade' => $post['usuarios_escolaridade'] ?? '',
            'usuarios_email' => trim((string) ($post['usuarios_email'] ?? '')),
            'usuarios_senha' => (string) ($post['usuarios_senha'] ?? ''),
            'usuarios_nivel' => $post['usuarios_nivel'] ?? '',
        ];

        if ($erro = Validacao::usuario($dados)) {
            $this->voltarComErro($voltar, $erro, $post);
        }

        if ($this->usuarios->emailEmUso($dados['usuarios_email'])) {
            $this->voltarComErro($voltar, 'Email ja cadastrado!', $post);
        }

        try {
            $imagem = Upload::imagem($_FILES['usuarios_imagem'] ?? null, 'Public/uploadImages');
        } catch (RuntimeException $e) {
            $this->voltarComErro($voltar, $e->getMessage(), $post, 'warning');
        }

        try {
            $novoId = $this->usuarios->criar([
                'usuarios_nome' => $dados['usuarios_nome'],
                'usuarios_escolaridade' => $dados['usuarios_escolaridade'],
                'usuarios_email' => $dados['usuarios_email'],
                'usuarios_senha' => password_hash($dados['usuarios_senha'], PASSWORD_DEFAULT),
                'usuarios_imagem' => $imagem,
                'usuarios_ativo' => 1,
                'usuarios_nivel' => (int) $dados['usuarios_nivel'],
            ]);
        } catch (\PDOException $e) {
            Upload::remover($imagem);
            $mensagem = $e->getCode() === '23000' ? 'Email ja cadastrado!' : 'Erro ao realizar o cadastro, tente novamente!';
            $this->voltarComErro($voltar, $mensagem, $post);
        }

        Logger::info('Usuário criado pelo administrador', ['novo_usuario' => $novoId, 'nivel' => (int) $dados['usuarios_nivel']]);
        $this->redirect($voltar, 'Cadastro realizado com sucesso!');
    }

    public function detailsUser(string $id): void
    {
        $usuario = $this->usuarios->buscarPorId($this->idDaUrlOu400($id));

        if (!$usuario) {
            $this->redirect(base_url('adm/usuarios'), 'Usuário não encontrado.', 'danger');
        }

        $this->loadView('adm/userDetails', ['usuarios' => $usuario]);
    }

    public function editarUsuario(): void
    {
        $post = $_POST;
        $usuarioId = (int) ($post['usuarios_id'] ?? 0);
        $voltar = base_url('adm/detailsUser/' . id_url($usuarioId));
        $usuario = $this->usuarios->buscarPorId($usuarioId);

        if (!$usuario) {
            $this->redirect(base_url('adm/usuarios'), 'Usuário não encontrado.', 'danger');
        }

        $dados = [
            'usuarios_nome' => trim((string) ($post['usuarios_nome'] ?? $usuario->usuarios_nome)),
            'usuarios_escolaridade' => $post['usuarios_escolaridade'] ?? $usuario->usuarios_escolaridade,
            'usuarios_email' => trim((string) ($post['usuarios_email'] ?? $usuario->usuarios_email)),
            'usuarios_ativo' => (string) ($post['usuarios_ativo'] ?? $usuario->usuarios_ativo),
            'usuarios_nivel' => (int) ($post['usuarios_nivel'] ?? $usuario->usuarios_nivel),
        ];

        if ($erro = Validacao::usuario($dados, false)) {
            $this->redirect($voltar, $erro, 'danger');
        }

        if ($this->usuarios->emailEmUso($dados['usuarios_email'], $usuarioId)) {
            $this->redirect($voltar, 'Email ja cadastrado!', 'danger');
        }

        // O administrador não pode se desativar nem remover o próprio acesso.
        if ($usuarioId === $this->usuarioId() && ($dados['usuarios_ativo'] !== '1' || $dados['usuarios_nivel'] !== 2)) {
            $this->redirect($voltar, 'Você não pode desativar ou rebaixar a sua própria conta.', 'danger');
        }

        // Troca de senha opcional (os campos existiam na tela, mas eram ignorados).
        $novaSenha = (string) ($post['nova_senha'] ?? '');

        if ($novaSenha !== '') {
            if ($novaSenha !== (string) ($post['confirmar_senha'] ?? '')) {
                $this->redirect($voltar, 'Os campos de senha não podem ser diferentes!', 'danger');
            }

            if ($erro = Validacao::senha($novaSenha)) {
                $this->redirect($voltar, $erro, 'danger');
            }

            $dados['usuarios_senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
        }

        $dados['usuarios_ativo'] = (int) $dados['usuarios_ativo'];
        $dados['usuarios_atualizado_em'] = date('Y-m-d H:i:s');

        $arquivo = $_FILES['usuarios_imagem'] ?? null;

        if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $dados['usuarios_imagem'] = Upload::imagem($arquivo, 'Public/uploadImages');
            } catch (RuntimeException $e) {
                $this->redirect($voltar, $e->getMessage(), 'danger');
            }
        }

        $this->usuarios->atualizar($usuarioId, $dados);

        if (isset($dados['usuarios_imagem']) && $usuario->usuarios_imagem !== $dados['usuarios_imagem']
            && !str_ends_with((string) $usuario->usuarios_imagem, 'Noimage.png')) {
            Upload::remover($usuario->usuarios_imagem);
        }

        Logger::info('Usuário editado pelo administrador', ['alvo' => $usuarioId]);
        $this->redirect(base_url('adm/usuarios'), 'Usuário editado com sucesso!');
    }

    public function deletarUsuario(string $id): void
    {
        $usuarioId = $this->idDaUrlOu400($id);

        if ($usuarioId === $this->usuarioId()) {
            $this->redirect(base_url('adm/detailsUser/' . $id), 'Você não pode excluir a sua própria conta.', 'danger');
        }

        if (!$this->usuarios->desativar($usuarioId)) {
            $this->redirect(base_url('adm/detailsUser/' . $id), 'Erro ao excluir este usuário!', 'danger');
        }

        Logger::info('Usuário desativado pelo administrador', ['alvo' => $usuarioId]);
        $this->redirect(base_url('adm/usuarios'), 'Usuário excluído com sucesso!');
    }
}
