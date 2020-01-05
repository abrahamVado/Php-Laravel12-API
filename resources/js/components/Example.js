import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import Button from '@material-ui/core/Button';

export default class Example extends Component {
    render() {
        return (
            <div className="container">
                Laravel with ReactJS
                <div className="row">
                    <span className="badge badge-light">&&</span>
                </div>
                <div className="row">
                    <Button variant="contained" color="primary">
                        Material ui
                    </Button> 
                </div>
            </div>
        );
    }
}

if (document.getElementById('example')) {
    ReactDOM.render(<Example />, document.getElementById('example'));
}
