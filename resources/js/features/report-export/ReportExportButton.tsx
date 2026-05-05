import { useMutation } from '@tanstack/react-query';
import { Download } from 'lucide-react';
import { toast } from 'sonner';
import ReportExportController from '@/actions/App/Http/Controllers/ReportExportController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type ReportExportButtonProps = {
    type: 'orders' | 'inventory' | 'revenue';
    dateFrom: string;
    dateTo: string;
    label?: string;
    className?: string;
};

export function ReportExportButton({
    type,
    dateFrom,
    dateTo,
    label = 'Download Report',
    className,
}: ReportExportButtonProps) {
    const mutation = useMutation({
        mutationFn: async () => {
            const url = ReportExportController({
                query: {
                    type,
                    date_from: dateFrom,
                    date_to: dateTo,
                },
            }).url;
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                },
            });

            if (!response.ok) {
                throw new Error('Report download failed');
            }

            const blob = await response.blob();
            const downloadUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `oms-${type}-${dateFrom}-${dateTo}.xlsx`;
            link.click();
            URL.revokeObjectURL(downloadUrl);
        },
        onSuccess: () => toast.success('Report downloaded successfully.'),
        onError: () =>
            toast.error('We could not download the report. Please try again.'),
    });

    return (
        <Button
            type="button"
            variant="outline"
            onClick={() => mutation.mutate()}
            disabled={mutation.isPending}
            className={cn(className)}
        >
            <Download className="size-4" />
            {mutation.isPending ? 'Downloading...' : label}
        </Button>
    );
}
