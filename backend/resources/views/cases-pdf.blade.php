<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 9px; color: #1e293b; }

  .header { background: #1a4972; color: #fff; padding: 12px 16px; margin-bottom: 14px; }
  .header h1 { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
  .header p  { font-size: 8px; color: #93c5fd; margin-top: 2px; }

  .meta { padding: 0 16px 10px; font-size: 8px; color: #64748b; }
  .meta strong { color: #1a4972; margin-right: 4px; }
  .meta span   { margin-right: 14px; }

  table { width: 100%; border-collapse: collapse; margin: 0 0 8px; font-size: 8.5px; }
  thead tr { background: #1a4972; color: #fff; }
  thead th { padding: 6px 5px; text-align: left; font-size: 8px; letter-spacing: 0.3px; white-space: nowrap; }

  tbody tr:nth-child(even) { background: #eff6ff; }
  tbody td { padding: 5px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }

  .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 7.5px; font-weight: bold; }
  .badge-urgent   { background: #fee2e2; color: #b91c1c; }
  .badge-normal   { background: #dbeafe; color: #1d4ed8; }
  .badge-low      { background: #f1f5f9; color: #475569; }
  .badge-active   { background: #d1fae5; color: #065f46; }
  .badge-closed   { background: #fee2e2; color: #991b1b; }
  .badge-archived { background: #fef3c7; color: #92400e; }

  .footer { margin-top: 10px; padding: 0 16px; font-size: 7.5px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<div class="header">
  <h1>Case Master — Export</h1>
  <p>Generated {{ $exportedAt }}</p>
</div>

@if(!empty($filters))
<div class="meta">
  <strong>Active filters:</strong>
  @foreach($filters as $label => $val)
    <span>{{ $label }}: {{ ucfirst($val) }}</span>
  @endforeach
</div>
@endif

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Case Code</th>
      <th>Case No.</th>
      <th>Title</th>
      <th>Category</th>
      <th>Client</th>
      <th>Lawyer</th>
      <th>Clerk</th>
      <th>Stage</th>
      <th>Priority</th>
      <th>Status</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $i => $c)
    <tr>
      <td style="color:#94a3b8">{{ $i + 1 }}</td>
      <td><strong>{{ $c->case_code }}</strong></td>
      <td>{{ $c->case_no }}</td>
      <td>{{ $c->title }}</td>
      <td>{{ $c->category_name ?? '—' }}</td>
      <td>{{ $c->client_name  ?? '—' }}</td>
      <td>{{ $c->lawyer_name  ?? '—' }}</td>
      <td>{{ $c->clerk_name   ?? '—' }}</td>
      <td>{{ $c->stage_name   ?? '—' }}</td>
      <td>
        <span class="badge badge-{{ $c->priority }}">{{ ucfirst($c->priority) }}</span>
      </td>
      <td>
        <span class="badge badge-{{ $c->case_status }}">{{ ucfirst($c->case_status) }}</span>
      </td>
      <td style="color:#64748b">
        {{ $c->created_at ? \Carbon\Carbon::parse($c->created_at)->format('Y-m-d') : '—' }}
      </td>
    </tr>
    @empty
    <tr><td colspan="12" style="text-align:center;padding:20px;color:#94a3b8">No cases found.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">
  Total: {{ count($rows) }} case(s) &nbsp;|&nbsp; Case Master System
</div>

</body>
</html>
