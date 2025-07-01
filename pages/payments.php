
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Gerenciar Pagamentos</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Buscar por cliente...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control">
                            <option>Todos os status</option>
                            <option>Pendente</option>
                            <option>Pago</option>
                            <option>Vencido</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary">
                            <i class="bi bi-plus"></i> Novo Pagamento
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>João Silva</td>
                                <td>R$ 150,00</td>
                                <td>15/01/2024</td>
                                <td><span class="badge bg-warning">Pendente</span></td>
                                <td>
                                    <button class="btn btn-sm btn-success">Confirmar</button>
                                    <button class="btn btn-sm btn-primary">PIX</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
