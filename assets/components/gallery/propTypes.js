import PropTypes from 'prop-types';

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
    editUrl: PropTypes.string.isRequired,
    deleteUrl: PropTypes.string.isRequired,
    size: PropTypes.string.isRequired,
    dateCreated: PropTypes.instanceOf(Date).isRequired,
});

export const MediaItemList = PropTypes.arrayOf(MediaItem);
