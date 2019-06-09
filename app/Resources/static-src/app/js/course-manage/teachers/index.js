// import ReactDOM from 'react-dom';
// import React from 'react';
// import PersonaMultiInput from 'app/common/component/persona-multi-input';
// import sortList from 'common/sortable';

// ReactDOM.render( 
//   <PersonaMultiInput 
//     addable={true}  
//     dataSource= {$('#course-teachers').data('init-value')}  
//     outputDataElement='teachers' 
//     inputName="ids[]"
//     searchable={{enable:true,url:$('#course-teachers').data('query-url') + '?q='}} 
//   />,
//   document.getElementById('course-teachers')
// );

// $('.js-btn-save').on('click', function(event){
//   if($('input[name=teachers]').val() !== '[]'){
//     $('#teachers-form').submit();
//   }else{
//     cd.message({ type: 'warning', message: Translator.trans('course.manage.min_teacher_num_error_hint')});
//   }
// });

class StudentAdd {
  constructor() {
    this.init();
  }

  init() {
    let $form = $('#student-add-form');
    let validator = $form.validate({
      onkeyup: false,
      currentDom:'#student-add-submit',
      rules: {
        queryfield: {
          required: true,
          remote: {
            url: $('#student-nickname').data('url'),
            type: 'get',
            data: {
              'value': function() {
                return $('#student-nickname').val();
              }
            }
          }
        }
      },
      messages: {
        queryfield: {
          remote: Translator.trans('course_manage.student_create.field_required_error_hint')
        }
      }
    });

    $('#student-add-submit').click(function(event) {
      if(validator.form()) {
        $form.submit();
      }
    });
  }
}

new StudentAdd();
