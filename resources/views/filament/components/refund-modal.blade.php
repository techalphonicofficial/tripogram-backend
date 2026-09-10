<div class="space-y-4 p-2">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Refund Amount</p>
            <p class="font-semibold text-lg text-red-600">₹{{ number_format($refund->amount, 2) }}</p>
        </div>
        
        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Refund Type</p>
            <p class="font-semibold">
                <span class="px-2 py-1 rounded text-xs {{ $refund->type === 'full' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($refund->type) }}
                </span>
            </p>
        </div>
        
        <div class="bg-gray-50 p-3 rounded col-span-2">
            <p class="text-sm text-gray-600">Refund Reason</p>
            <p class="font-semibold">{{ $refund->reason ?? 'N/A' }}</p>
        </div>
        
        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Refund Date</p>
            <p class="font-semibold">{{ $refund->created_at ? $refund->created_at->format('d M Y h:i A') : 'N/A' }}</p>
        </div>
        
        <div class="bg-gray-50 p-3 rounded">
            <p class="text-sm text-gray-600">Status</p>
            <p class="font-semibold">
                <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">
                    Pending
                </span>
            </p>
        </div>
        
        @if($refund->image)
        <div class="col-span-2">
            <p class="text-sm text-gray-600 mb-2">Refund Proof / Screenshot</p>
            <div class="bg-gray-100 p-3 rounded text-center">
                <img src="{{ asset('storage/' . $refund->image) }}" 
                     class="max-w-full h-auto rounded shadow-sm mx-auto" 
                     style="max-height: 300px; width: auto;">
                <a href="{{ asset('storage/' . $refund->image) }}" 
                   target="_blank" 
                   class="text-blue-600 hover:text-blue-800 text-sm mt-2 inline-block">
                    🔗 View Full Size
                </a>
            </div>
        </div>
        @endif
    </div>
</div>