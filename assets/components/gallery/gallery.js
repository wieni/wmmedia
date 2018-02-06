import 'event-propagation-path';
import './styles/index.scss';

import React from 'react';
import ReactDOM from 'react-dom';
import MediaGrid from './MediaGrid';

const container = document.querySelector('[data-media]');
if (container) {
    ReactDOM.render(
        <MediaGrid {...JSON.parse(container.dataset.media)} />,
        container,
    );
}
