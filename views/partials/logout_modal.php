<!-- Modal de logout -->
<div class="modal fade" id="meuModalSair" tabindex="-1" aria-labelledby="tituloModalSair" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalSair" style="text-align: center !important; font-size: x-large;"></h5>
            </div>

            <div class="modal-body">
                <form action="<?= base_url('login/logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <h4 class="text-center mt-4 mb-5">Deseja sair da sua conta?</h4>

                    <p class="text-center">
                        <button type="button" class="btn-voltar" data-bs-dismiss="modal">Voltar</button>
                        <button type="submit" class="btn-finalizar">Sair</button>
                    </p>
                </form>
            </div>

            <div class="modal-footer"></div>

        </div>
    </div>
</div>
