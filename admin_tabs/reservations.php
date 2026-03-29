<div class="card">
    <div class="card-header"><span class="card-title">Manage Reservations</span></div>
    <table>
        <tr>
            <th>Customer</th>
            <th>Date/Time</th>
            <th>Guests</th>
            <th>Table</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        try { $pdo->exec("ALTER TABLE reservations ADD COLUMN table_id INT DEFAULT NULL"); } catch (Exception $e) {}
        $res = $pdo->query("SELECT r.*, t.table_name FROM reservations r LEFT JOIN restaurant_tables t ON r.table_id = t.id ORDER BY r.reservation_date DESC, r.reservation_time DESC")->fetchAll();
        foreach ($res as $r):
            ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($r['customer_name']) ?></strong><br>
                    <small style="color: #64748b; font-size: 11px;"><?= htmlspecialchars($r['email'] ?? '') ?></small>
                </td>
                <td>
                    <?= $r['reservation_date'] ?><br><small>
                        <?= date("g:i A", strtotime($r['reservation_time'])) ?>
                    </small>
                </td>
                <td>
                    <span class="badge" style="background: #f1f5f9; color: #475569;"><?= $r['guests'] ?> Persons</span>
                </td>
                <td>
                    <?php if (!empty($r['table_name'])): ?>
                        <div style="font-weight: 700; color: var(--deep-saffron); font-size: 13px;">
                            <i class="fa-solid fa-table"></i> <?= htmlspecialchars($r['table_name']) ?>
                        </div>
                    <?php else: ?>
                        <div style="font-style: italic; color: #94a3b8; font-size: 11px;">Manual Assign</div>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: flex; gap: 5px; margin-top: 5px;">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="status" value="<?= $r['status'] ?>">
                        <input type="text" name="table_number" placeholder="#" 
                               value="<?= htmlspecialchars($r['table_number'] ?? '') ?>" 
                               style="width: 50px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; font-size: 11px;">
                        <button type="submit" name="update_reservation" value="1" 
                                style="padding: 5px 8px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; cursor: pointer; font-size: 10px;">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                </td>
                <td><span class="badge <?= strtolower($r['status']) ?>">
                        <?= $r['status'] ?>
                    </span></td>
                <td>
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                        <form method="POST" class="flex-actions">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="table_number" value="<?= htmlspecialchars($r['table_number'] ?? '') ?>">
                            <select name="status" onchange="this.form.submit()"
                                style="width:110px; padding:5px; border-radius: 6px; border: 1px solid #ddd; font-size: 11px;">
                                <option value="Pending" <?= $r['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= $r['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed
                                </option>
                                <option value="Rejected" <?= $r['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                            <input type="hidden" name="update_reservation" value="1">
                        </form>
                        <span
                            style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase;">Status</span>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>