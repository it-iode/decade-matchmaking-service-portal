import { ImgHTMLAttributes } from 'react';
import { Link, usePage } from '@inertiajs/react';


export default function FlanderLogo(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <Link href={route('index')} className="inline-flex items-center">
            <img src='/assets/img/flander_logo.png' alt="Flanders Logo" className="h-20 w-auto object-contain md:h-28" {...props} />
        </Link>

    );
}
