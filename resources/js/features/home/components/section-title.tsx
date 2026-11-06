import React from 'react';

interface SectionTitleProps extends React.HTMLAttributes<HTMLHeadingElement> {
    children: React.ReactNode;
    className?: string;
}

const SectionTitle: React.FC<SectionTitleProps> = ({children, className = '', ...props}) => (
    <div
        className="my-6 lg:my-4 container flex flex-col md:flex-row items-start md:items-center justify-between pb-4 border-b border-gray-300 dark:border-gray-700">
        <div>
            <h4
                className={`text-2xl font-bold leading-tight text-gray-800 dark:text-gray-100 ${className}`.trim()}
                {...props}
            >
                {children}
            </h4>
        </div>
    </div>

);

export default SectionTitle;
