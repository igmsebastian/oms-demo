import { router } from '@inertiajs/react';
import { Banknote, RefreshCcw } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { refundStockDisposition } from '@/entities/order/model/refund-stock-disposition';
import type { RefundStockDisposition } from '@/entities/order/model/refund-stock-disposition';
import type { Order } from '@/entities/order/model/types';
import { store as requestRefund } from '@/routes/orders/refunds';
import { completed, processing as markProcessing } from '@/routes/refunds';
import { ConfirmationDialog } from '@/shared/components/ConfirmationDialog';

type OrderRefundActionProps = {
    order: Order;
    mode: 'request' | 'processing' | 'complete';
};

export function OrderRefundAction({ order, mode }: OrderRefundActionProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [stockDisposition, setStockDisposition] =
        useState<RefundStockDisposition>(refundStockDisposition.goodStock);
    const [note, setNote] = useState('');
    const latestRefund = order.refunds?.at(-1);

    if (mode === 'complete' && latestRefund) {
        return (
            <>
                <Button
                    type="button"
                    className="bg-green-600 text-white hover:bg-green-700"
                    onClick={() => setOpen(true)}
                >
                    <Banknote className="size-4" />
                    Complete Refund
                </Button>
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Complete refund</DialogTitle>
                            <DialogDescription>
                                Choose how returned stock should be handled.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label required>Stock result</Label>
                                <Select
                                    value={stockDisposition}
                                    onValueChange={(value) =>
                                        setStockDisposition(
                                            value as RefundStockDisposition,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            value={
                                                refundStockDisposition.goodStock
                                            }
                                        >
                                            Good Stock
                                        </SelectItem>
                                        <SelectItem
                                            value={
                                                refundStockDisposition.badStock
                                            }
                                        >
                                            Bad Stock
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="refund-note">Note</Label>
                                <textarea
                                    id="refund-note"
                                    value={note}
                                    onChange={(event) =>
                                        setNote(event.target.value)
                                    }
                                    className="min-h-24 rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                variant="outline"
                                disabled={processing}
                                onClick={() => setOpen(false)}
                            >
                                Close
                            </Button>
                            <Button
                                className="bg-green-600 text-white hover:bg-green-700"
                                disabled={processing}
                                onClick={() => {
                                    setProcessing(true);
                                    const request = completed.patch(
                                        latestRefund.id,
                                    );
                                    router.visit(request.url, {
                                        method: request.method,
                                        data: {
                                            stock_disposition: stockDisposition,
                                            note,
                                        },
                                        preserveState: true,
                                        preserveScroll: true,
                                        onError: () =>
                                            toast.error(
                                                'We could not complete the refund. Please try again.',
                                            ),
                                        onFinish: () => {
                                            setProcessing(false);
                                            setOpen(false);
                                        },
                                    });
                                }}
                            >
                                {processing ? 'Working...' : 'Complete Refund'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </>
        );
    }

    const label = mode === 'processing' ? 'Process Refund' : 'Request Refund';

    return (
        <>
            <Button
                type="button"
                variant={mode === 'request' ? 'outline' : 'default'}
                className={
                    mode === 'request'
                        ? 'border-amber-300 text-amber-700 hover:bg-amber-50'
                        : undefined
                }
                onClick={() => setOpen(true)}
            >
                <RefreshCcw className="size-4" />
                {label}
            </Button>
            <ConfirmationDialog
                open={open}
                onOpenChange={setOpen}
                title={label}
                confirmLabel={label}
                noteLabel="Reason"
                notePlaceholder="Refund reason"
                processing={processing}
                onConfirm={(reason) => {
                    setProcessing(true);
                    const request =
                        mode === 'processing' && latestRefund
                            ? markProcessing.patch(latestRefund.id)
                            : requestRefund.post(order.order_number);

                    router.visit(request.url, {
                        method: request.method,
                        data:
                            mode === 'request'
                                ? { amount: order.total_amount, reason }
                                : {},
                        preserveState: true,
                        preserveScroll: true,
                        onError: () =>
                            toast.error(
                                'We could not complete the refund action. Please try again.',
                            ),
                        onFinish: () => {
                            setProcessing(false);
                            setOpen(false);
                        },
                    });
                }}
            />
        </>
    );
}
