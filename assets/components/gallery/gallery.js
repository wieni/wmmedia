import 'event-propagation-path';
import 'isomorphic-fetch';
import './styles/index.scss';

import React from 'react';
import ReactDOM from 'react-dom';
import MediaGrid from './MediaGrid';
import { parse } from 'date-fns';

const container = document.querySelector('[data-media]');
if (container) {
    const props = JSON.parse(container.dataset.media);
    props.items = props.items.map(item => ({
        ...item,
        dateCreated: parse(item.dateCreated * 1000),
        dateChanged: parse(item.dateChanged * 1000),
    }));

    ReactDOM.render(
        <MediaGrid {...props} />,
        container,
    );
}
