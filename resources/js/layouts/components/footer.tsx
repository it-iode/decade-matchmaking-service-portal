import React from 'react';
import {Link} from '@inertiajs/react';
import {ApplicationLogo, FlanderLogo} from '@shared/components';
import {Instagram, Facebook, Linkedin} from 'lucide-react';

const Header: React.FC = () => {
    return (
        <section id="footer" className="bg-gray-700 dark:bg-gray-950 py-8 px-4">
            <div className='container mx-auto'>
                <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-[auto_auto_1fr_auto_auto] gap-4 md:gap-6 items-start'>
                    {/* ApplicationLogo - takes natural width */}
                    <div className="flex justify-center md:justify-start">
                        <ApplicationLogo/>
                    </div>

                    {/* FlanderLogo - takes natural width */}
                    <div className="flex justify-center md:justify-start">
                        <FlanderLogo/>
                    </div>

                    {/* Contact Section - grows to fill space */}
                    <div className="text-center md:text-left">
                        <span className='text-base font-bold text-white'>Contact</span>
                        <p className='text-sm text-white'>Capacity Development Facility</p>
                        <p className='text-sm text-white'>UNESCO Headquarters, 7 Place de Fontenoy, 75007</p>
                        <p className='text-sm text-white'>Paris <br/> France</p>
                        <a className='text-sm text-white hover:underline' href="mailto:cdf@unesco.org">cdf@unesco.org</a>
                    </div>

                    {/* Links Section - takes natural width */}
                    <div className="text-center md:text-left">
                        <span className='text-base font-bold text-white'>Links</span>
                        <ul className='no-list-style space-y-1'>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='Ocean Decade Website'
                                   className='text-sm text-white hover:underline' href="http://oceandecade.org">
                                    Ocean Decade Website
                                </a>
                            </li>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='IOC-UNESCO'
                                   className='text-sm text-white hover:underline'
                                   href="http://ioc.unesco.org">
                                    IOC-UNESCO
                                </a>
                            </li>
                        </ul>
                    </div>

                    {/* Social Media Section - takes natural width */}
                    <div className="flex justify-center md:justify-end">
                        <ul className='no-list-style flex flex-row gap-4'>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='Twitter'
                                   className='text-white hover:text-gray-300 transition-colors'
                                   href="https://twitter.com/oceandecade">
                                    <svg className="text-white" height="24px" viewBox="0 0 24 24" width="24px"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <g fill="none" fillRule="evenodd" stroke="none" strokeWidth="1">
                                            <g fill="currentColor" fillRule="nonzero" transform="translate(0, 1.1)">
                                                <path
                                                    d="M18.8996089,0 L22.5814863,0 L14.5397653,9.21237981 L24,21.75 L16.5945241,21.75 L10.7900913,14.1479567 L4.15645372,21.75 L0.469361147,21.75 L9.06910039,11.8945312 L0,0 L7.59322034,0 L12.8344198,6.9484976 L18.8996089,0 Z M17.6062581,19.5436298 L19.6453716,19.5436298 L6.48239896,2.09134615 L4.29204694,2.09134615 L17.6062581,19.5436298 Z"></path>
                                            </g>
                                        </g>
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='Instagram'
                                   className='text-white hover:text-gray-300 transition-colors'
                                   href="https://www.instagram.com/unoceandecade/">
                                    <Instagram/>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='Facebook'
                                   className='text-white hover:text-gray-300 transition-colors'
                                   href="https://www.facebook.com/oceandecade">
                                    <Facebook/>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" title='LinkedIn'
                                   className='text-white hover:text-gray-300 transition-colors'
                                   href="https://www.linkedin.com/company/un-ocean-decade/">
                                    <Linkedin/>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Header;
