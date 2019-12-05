import PropTypes from 'prop-types';

const MediaOperation = PropTypes.shape({
    key: PropTypes.string.isRequired,
    title: PropTypes.string.isRequired,
    url: PropTypes.string.isRequired,
});

export const MediaItem = PropTypes.shape({
    label: PropTypes.string.isRequired,
    author: PropTypes.string.isRequired,
    copyright: PropTypes.string,
    caption: PropTypes.string,
    alternate: PropTypes.string,
    height: PropTypes.number,
    width: PropTypes.number,
    originalUrl: PropTypes.string,
    largeUrl: PropTypes.string,
    thumbUrl: PropTypes.string,
    size: PropTypes.string,
    dateCreated: PropTypes.instanceOf(Date).isRequired,
    operations: PropTypes.arrayOf(MediaOperation).isRequired,
});

export const MediaItemList = PropTypes.arrayOf(MediaItem);
