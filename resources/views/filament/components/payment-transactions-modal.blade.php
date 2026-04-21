<div class="p-6">
    @if(!empty($transactions) && is_array($transactions))
        <div class="space-y-6">
            @foreach($transactions as $index => $transaction)
                <div class="border rounded-lg p-4 {{ $index > 0 ? 'mt-4' : '' }}">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">UTR Number / Transaction ID</label>
                            <p class="text-lg font-semibold text-gray-900">{{ $transaction['utr_number'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Transaction Date</label>
                            <p class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::parse($transaction['transaction_date'])->format('d M, Y') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Amount</label>
                            <p class="text-lg font-semibold text-green-600">₹{{ number_format($transaction['amount'] ?? 0, 2) }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Payment Type</label>
                            <p class="text-lg font-semibold text-gray-900">
                                @php
                                    $paymentType = $transaction['payment_type'] ?? 'partial';
                                    $typeLabel = [
                                        'full' => 'Full Payment',
                                        'half' => 'Half Payment',
                                        'partial' => 'Partial Payment'
                                    ][$paymentType] ?? ucfirst($paymentType);
                                @endphp
                                {{ $typeLabel }}
                            </p>
                        </div>
                    </div>
                    
                    @if(!empty($transaction['notes']))
                        <div class="mb-4">
                            <label class="text-sm font-medium text-gray-700">Notes</label>
                            <p class="text-gray-600">{{ $transaction['notes'] }}</p>
                        </div>
                    @endif
                    
                    @if(!empty($transaction['screenshot']))
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-2 block">Screenshots</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach((array)$transaction['screenshot'] as $screenshot)
                                    @php
                                        $imageUrl = \Storage::url($screenshot);
                                    @endphp
                                    <div class="relative group">
                                        <img src="{{ $imageUrl }}" 
                                             alt="Payment Screenshot" 
                                             class="w-full h-32 object-cover rounded-lg shadow-md cursor-pointer"
                                             onclick="openImageModal('{{ $imageUrl }}')">
                                        <a href="{{ $imageUrl }}" 
                                           download
                                           class="absolute top-2 right-2 bg-white rounded-full p-1 shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            
            <!-- Summary -->
            <div class="border-t pt-4 mt-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-700">Total Paid:</span>
                    <span class="text-xl font-bold text-green-600">
                        ₹{{ number_format(array_sum(array_column($transactions, 'amount')), 2) }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Image Modal -->
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50" onclick="closeImageModal()">
            <div class="relative max-w-4xl max-h-full p-4">
                <img id="modalImage" src="" alt="Full size screenshot" class="max-w-full max-h-full rounded-lg shadow-2xl">
                <button onclick="closeImageModal()" class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <script>
            function openImageModal(imageUrl) {
                document.getElementById('modalImage').src = imageUrl;
                document.getElementById('imageModal').classList.remove('hidden');
                document.getElementById('imageModal').classList.add('flex');
            }
            
            function closeImageModal() {
                document.getElementById('imageModal').classList.add('hidden');
                document.getElementById('imageModal').classList.remove('flex');
            }
        </script>
        
        <style>
            #imageModal {
                cursor: pointer;
            }
            #imageModal .relative {
                cursor: default;
            }
        </style>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">No payment transactions found for this booking.</p>
        </div>
    @endif
</div>