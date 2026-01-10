<?php

declare(strict_types=1);

return [
    'success' => 'Operação concluída com sucesso.',
    'error' => 'Ocorreu um erro.',
    'not_found' => 'Recurso não encontrado.',
    'created' => ':resource criado com sucesso.',
    'updated' => ':resource atualizado com sucesso.',
    'deleted' => ':resource excluído com sucesso.',
    'restored' => ':resource restaurado com sucesso.',
    'archived' => ':resource arquivado com sucesso.',

    // Wallet messages
    'wallet' => [
        'balance_calculated' => 'Saldo calculado com sucesso.',
        'insufficient_balance' => 'Saldo insuficiente.',
        'transfer_completed' => 'Transferência concluída com sucesso.',
        'transfer_failed' => 'Falha na transferência.',
        'cannot_delete' => 'Carteiras não podem ser excluídas.',
        'cannot_transfer_to_same' => 'Não é possível transferir para a mesma carteira.',
    ],

    // Transaction messages
    'transaction' => [
        'credit_added' => 'Crédito adicionado com sucesso.',
        'debit_added' => 'Débito adicionado com sucesso.',
        'invalid_amount' => 'O valor deve ser maior que zero.',
        'immutable' => 'Transações não podem ser modificadas.',
    ],

    // Timer messages
    'timer' => [
        'started' => 'Timer iniciado.',
        'paused' => 'Timer pausado.',
        'resumed' => 'Timer retomado.',
        'stopped' => 'Timer parado.',
        'cancelled' => 'Timer cancelado.',
        'invalid_state' => 'Estado do timer inválido.',
        'already_running' => 'O timer já está em execução.',
        'debit_description' => 'Tempo registrado',
    ],

    // Invoice messages
    'invoice' => [
        'created' => 'Fatura criada com sucesso.',
        'paid' => 'Fatura marcada como paga.',
        'cancelled' => 'Fatura cancelada.',
        'cannot_modify' => 'Faturas pagas não podem ser modificadas.',
        'invalid_price' => 'O preço por hora deve ser maior que zero.',
        'no_debt' => 'A carteira não possui débitos.',
        'payment_description' => 'Pagamento da fatura #:invoice_id',
        'payment_internal_note' => 'Pagamento da fatura #:invoice_id recebido',
    ],

    // Package messages
    'package' => [
        'purchased' => 'Pacote adquirido com sucesso.',
        'inactive' => 'Este pacote não está disponível.',
        'purchase_disabled' => 'Compras estão desabilitadas para esta carteira.',
    ],
];
