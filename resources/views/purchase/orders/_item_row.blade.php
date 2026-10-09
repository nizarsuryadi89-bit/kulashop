<tr>
    <td>
        <select name="items[{{ $index }}][product_id]" class="form-select select2-products" required>
            <option value="">Pilih Produk...</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" {{ (isset($item) && $item['product_id'] == $p->id) ? 'selected' : '' }}>
                    {{ $p->sku }} - {{ $p->product_name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][qty]" class="form-control input-qty" 
               value="{{ $item['qty'] ?? ($item['qty_ordered'] ?? 1) }}" min="0.01" step="0.01" required>
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][unit_price]" class="form-control input-price" 
               value="{{ floatval($item['unit_price'] ?? 0) }}" min="0" step="0.01" required>
    </td>
    <td>
        <input type="number" name="items[{{ $index }}][discount]" class="form-control input-discount" 
               value="{{ floatval($item['discount'] ?? 0) }}" min="0" step="0.01">
    </td>
    <td class="align-middle fw-semibold row-subtotal-display">Rp 0</td>
    <td class="text-center align-middle">
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
            <i class="fas fa-trash"></i>
        </button>
    </td>
</tr>
