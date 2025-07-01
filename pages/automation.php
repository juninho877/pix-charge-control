
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Automação de Cobranças</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>Lembretes Automáticos</h5>
                                <p>Configure quando enviar lembretes de vencimento.</p>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" checked>
                                    <label class="form-check-label">3 dias antes do vencimento</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" checked>
                                    <label class="form-check-label">No dia do vencimento</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox">
                                    <label class="form-check-label">3 dias após vencimento</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>Mensagem Personalizada</h5>
                                <textarea class="form-control" rows="5" placeholder="Olá {nome}, sua mensalidade no valor de R$ {valor} vence em {dias} dias."></textarea>
                                <small class="text-muted">Use {nome}, {valor}, {dias} como variáveis</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button class="btn btn-primary">Salvar Configurações</button>
                </div>
            </div>
        </div>
    </div>
</div>
