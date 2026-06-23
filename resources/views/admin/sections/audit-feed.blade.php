<div class="table-wrap"><table><thead><tr><th>Timestamp</th><th>User & Role</th><th>Action</th><th>Status</th></tr></thead><tbody>
    @forelse ($auditLogs as $log)
        <tr><td>{{ isset($log['created_at']) ? date('H:i:s', strtotime($log['created_at'])) : '' }}</td><td><strong>{{ $log['actor_name'] ?? 'System' }}</strong><br><span class="eyebrow">{{ $log['actor_role'] ?? 'Admin' }}</span></td><td>{{ $log['action'] }}</td><td><span class="badge">{{ $log['status'] ?? 'Success' }}</span></td></tr>
    @empty
        <tr><td>14:22:15</td><td><strong>Sarah Jenkins</strong><br><span class="eyebrow">Senior Nurse</span></td><td>Accessed Patient Record #1029</td><td><span class="badge">Success</span></td></tr>
        <tr><td>14:05:32</td><td><strong>Unknown User</strong><br><span class="eyebrow">Unauthorized</span></td><td>Unauthorized Login Attempt</td><td><span class="badge status-danger">Failed</span></td></tr>
    @endforelse
</tbody></table></div>
