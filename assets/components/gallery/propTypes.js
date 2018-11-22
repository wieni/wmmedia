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
    height: PropTypes.number.isRequired,
    width: PropTypes.number.isRequired,
    originalUrl: PropTypes.string.isRequired,
    largeUrl: PropTypes.string.isRequired,
    thumbUrl: PropTypes.string.isRequired,
    size: PropTypes.string.isRequired,
    dateCreated: PropTypes.instanceOf(Date).isRequired,
    operations: PropTypes.arrayOf(MediaOperation).isRequired,
});

export const MediaItemList = PropTypes.arrayOf(MediaItem);
