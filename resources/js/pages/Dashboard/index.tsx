import { Head } from '@inertiajs/react';
import type { DashboardData } from '@/entities/report/model/types';
import { dashboard as dashboardRoute } from '@/routes';
import { PageHeader } from '@/shared/components/PageHeader';
import { DashboardCharts } from '@/widgets/dashboard-charts/DashboardCharts';
import { DashboardKpis } from '@/widgets/dashboard-kpis/DashboardKpis';

type DashboardProps = {
    dashboard: DashboardData;
};

export default function Dashboard({ dashboard }: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col">
                <div className="@container/main flex flex-1 flex-col gap-2">
                    <div className="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                        <div className="px-4 lg:px-6">
                            <PageHeader
                                title="Dashboard"
                                description="Order, revenue, and inventory health."
                            />
                        </div>

                        <DashboardKpis data={dashboard} />

                        <div className="px-4 lg:px-6">
                            <DashboardCharts data={dashboard} />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboardRoute(),
        },
    ],
};
