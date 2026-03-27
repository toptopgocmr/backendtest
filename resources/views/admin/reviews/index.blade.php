{{-- resources/views/admin/reviews/index.blade.php --}}
@extends('admin.layouts.app')
@section('title', 'Avis clients')
@section('content')
<h2 style="font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;margin-bottom:8px">Avis clients</h2>
<p style="color:var(--txt3);font-size:13px;margin-bottom:20px">{{ $reviews->total() }} avis au total</p>

<div class="card">
  <table>
    <thead><tr><th>Client</th><th>Propriété</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Visible</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($reviews as $review)
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="avatar">{{ strtoupper(substr($review->user->name ?? 'A',0,1)) }}</div>
          <span>{{ $review->user->name ?? '—' }}</span>
        </div>
      </td>
      <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px">
        {{ $review->property->title ?? '—' }}
      </td>
      <td>
        <div style="display:flex;gap:2px">
          @for($i=1;$i<=5;$i++)
            <span style="color:{{ $i <= $review->rating ? 'var(--gold)' : 'var(--border)' }};font-size:14px">★</span>
          @endfor
        </div>
        <span style="font-size:11px;color:var(--txt3)">{{ $review->rating }}/5</span>
      </td>
      <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;color:var(--txt2)">
        {{ $review->comment ?? '—' }}
      </td>
      <td style="font-size:12px;color:var(--txt3)">{{ $review->created_at->format('d/m/Y') }}</td>
      <td>
        <span class="badge-status {{ $review->is_visible ? 'actif' : 'annulé' }}">
          {{ $review->is_visible ? 'Visible' : 'Masqué' }}
        </span>
      </td>
      <td>
        <div style="display:flex;gap:6px">
          <form action="{{ route('admin.reviews.toggle', $review->id) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn {{ $review->is_visible ? 'btn-danger' : 'btn-success' }} btn-sm">
              {{ $review->is_visible ? 'Masquer' : 'Afficher' }}
            </button>
          </form>
          <form action="{{ route('admin.reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Supprimer cet avis ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </td>
    </tr>
    @empty
    <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--txt3)">⭐ Aucun avis</td></tr>
    @endforelse
    </tbody>
  </table>
  <div style="padding:16px 20px;border-top:1px solid var(--border)">{{ $reviews->links() }}</div>
</div>
@endsection
