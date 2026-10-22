<?php
// Order state changes and refund decisions.
if ($action === 'cancel') cancel_order($id);
elseif (in_array($action, ['confirm', 'ship'], true)) advance_order($id, $action);
elseif ($action === 'accept_refund' || $action === 'reject_refund') decide_refund($id, $action === 'accept_refund');
else throw new DomainException('Tindakan tidak valid.');

flash('Status pesanan berhasil diperbarui.');
redirect('bangjanganbang/' . $route . '.php');
