'use strict';

/**
 * App module
 * @note all code in one file as it's quite short, may change eventually
 */
var ploufplouf = angular.module('ploufplouf', ['ngRoute', 'ngCookies']).config(['$routeProvider', function($routeProvider) {
    $routeProvider.
        when('/main', {templateUrl: 'form.html', controller: 'MainCtrl'}).
        when('/success', {templateUrl: 'success.html', controller: 'DoneCtrl'}).
        otherwise({redirectTo: '/main'});
}]);

/**
 * Form service
 * @note used in both controllers to share data between them (singleton)
 */
ploufplouf.service('dilemmaForm', function() {

    var defaultResults =  {
        success: 'NOT SEND',
        picked_id: null,
        picked_value: null,
        emails: null
    };

    var defaultForm = {
        question: '',
        name : '',
        email: '',
        new_choice: '',
        choices: [],
        new_email: '',
        emails: [],
        results: {}
    };

    // form values / inputs
    var service = {
        form: {}
        }
    };

    // add a choice
    service.addChoice = function () {
        if (service.form.new_choice) {
            service.form.choices.push(service.form.new_choice);
            service.form.new_choice = '';
        }
    };

    // remove a choice
    service.removeChoice = function(index) {
        service.form.choices.splice(index, 1);
    };

    // add an email
    service.addEmail = function () {
        if ((service.form.new_email) /* && (service.form.new_email.$valid)*/) {
            service.form.emails.push(service.form.new_email);
            service.form.new_email = '';
        }
    };

    // remove an email
    service.removeEmail = function(index) {
        service.form.emails.splice(index, 1);
    };

    /**
     * Validate data to send
     * @returns {boolean}
     */
    service.isValid = function() {

        // if (!$scope.question.length) { return false; }
        if (service.form.choices.length < 2) { return false; }

        if (service.form.emails.length) {
            if (!service.form.name.length) { return false; }
            if (!service.form.email.length) { return false; }
        }

        return true;
    };

    service.setResult = function(data) {
        if (data.status === 'SUCCESS') {
            service.form.results = angular.copy(data);
        }
    };

    service.isSuccess = function() {
        return service.form.results.status === 'SUCCESS';
    };

    service.resetForm = function() {
       angular.copy(defaultForm, service.form);
       angular.copy(defaultResults, service.form.results);
    };

    service.resetResult = function() {
        angular.copy(defaultResults, service.form.results);
    };

    // init
    service.resetForm();

    return service;
});

/**
 * App (form) controller
 */
ploufplouf.controller('MainCtrl', ['$scope', '$http', '$location', '$cookies', 'dilemmaForm',  function ($scope, $http, $location, $cookies, dilemmaForm) {

    $scope.errors = {}; /** @note may move errors ro service later */

    // bind form service to scope
    $scope.formActions = dilemmaForm; // all the service
    $scope.form = dilemmaForm.form; // just form data, alias

    // Get name & email from cookies
    if (angular.isDefined($cookies.name)) { dilemmaForm.form.name = $cookies.name; }
    if (angular.isDefined($cookies.email)) {dilemmaForm.form.email = $cookies.email; }

    $scope.populate = function(choices) {
        dilemmaForm.form.choices = choices;
    };

    $scope.reset = function() {
        dilemmaForm.form.choices = [];
    };

    $scope.hasErrors = function() {
        return (Object.keys($scope.errors).length > 0);
    };

    /**
     * Submit data to the backend and get the result
     */
    $scope.submit = function() {

        // if (isValid() === false) { return false; }
        $scope.errors = {};

        // store name & email in cookies
        if (angular.isString(dilemmaForm.form.name) && dilemmaForm.form.name !== "") { $cookies.name = dilemmaForm.form.name; }
        else { $cookies.name = null; }

        if (angular.isString(dilemmaForm.form.email) && dilemmaForm.form.email !== "") { $cookies.email = dilemmaForm.form.email; }
        else { $cookies.email = null; }


        // data to post
        var data = {
            question: dilemmaForm.form.question,
            name: dilemmaForm.form.name,
            email: dilemmaForm.form.email,
            choices: dilemmaForm.form.choices,
            emails: dilemmaForm.form.emails
        };

        $http.post('/submit', data).
            success(function(data, status, headers, config) {

                if (data.status === 'SUCCESS') {
                    dilemmaForm.setResult(data);
                    $location.path("/success");
                }

            }).
            error(function(data, status, headers, config) {
                $scope.errors = angular.copy(data.errors);
            }
        );
    };

}]);

/**
 * Result controller
 */
ploufplouf.controller('DoneCtrl', ['$scope', '$http', '$location', 'dilemmaForm', function ($scope, $http, $location, dilemmaForm) {

    if (dilemmaForm.form.results.success == 'NOT SEND') { $location.path("/main"); }

    // bind form service to scope
    $scope.formActions = dilemmaForm; // all the service
    $scope.form = dilemmaForm.form; // just form data, alias

    $scope.showEmails = function() {
        return angular.isNumber(dilemmaForm.form.results.emails);
    }

    $scope.newDilemma = function() {
        dilemmaForm.resetForm();
        $location.path("/main");
    }

    $scope.modifyDilemma = function() {
        dilemmaForm.resetResult();
        $location.path("/main");
    }

}]);
